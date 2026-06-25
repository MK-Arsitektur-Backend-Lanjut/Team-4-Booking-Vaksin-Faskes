import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// ===========================================================================
// 200-user Load / Stress test for the Queue & Appointment module.
//
// Run (Docker):
//   docker run --rm -v ${PWD}:/scripts \
//     -e BASE_URL=http://host.docker.internal:8000/api/v1 \
//     -e K6_MODE=load -e TARGET_VUS=200 \
//     grafana/k6 run /scripts/loadtest/queue/k6-load-200.js
//
// Modes (K6_MODE):  smoke | load | stress   (default: load)
//   smoke  -> 1 VU, 15s   (connectivity / script sanity)
//   load   -> ramp to TARGET_VUS, hold, ramp down (steady-state)
//   stress -> step up 25%/50%/75%/100% of TARGET_VUS to find the knee point
// ===========================================================================

// --- Configuration (override any with -e KEY=VALUE) -----------------------
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000/api/v1';
const MODE = (__ENV.K6_MODE || 'load').toLowerCase();

// Peak concurrency. The whole point of this script: 200 concurrent users.
const TARGET_VUS = __ENV.TARGET_VUS ? parseInt(__ENV.TARGET_VUS, 10) : 200;

// Stage durations (shorten these for a quick trial run).
const RAMP_UP = __ENV.RAMP_UP || '30s';
const HOLD = __ENV.HOLD || '2m';
const RAMP_DOWN = __ENV.RAMP_DOWN || '30s';

// Valid id ranges. MUST match seeded data — DatabaseSeeder seeds ~10,000
// health centers, ~10,000 schedules and ~10,000 patients. Spreading across them
// produces real successes and realistic row-lock contention (instead of everyone
// hammering schedule #1, which just fills one quota and returns 422 forever).
const SCHEDULE_MIN = __ENV.SCHEDULE_MIN ? parseInt(__ENV.SCHEDULE_MIN, 10) : 1;
const SCHEDULE_MAX = __ENV.SCHEDULE_MAX ? parseInt(__ENV.SCHEDULE_MAX, 10) : 10000;
const PATIENT_MIN = __ENV.PATIENT_MIN ? parseInt(__ENV.PATIENT_MIN, 10) : 1;
const PATIENT_MAX = __ENV.PATIENT_MAX ? parseInt(__ENV.PATIENT_MAX, 10) : 10000;
const HC_MIN = __ENV.HC_MIN ? parseInt(__ENV.HC_MIN, 10) : 1;
const HC_MAX = __ENV.HC_MAX ? parseInt(__ENV.HC_MAX, 10) : 10000;

const REQUEST_TIMEOUT = __ENV.REQUEST_TIMEOUT || '30s';
const THINK_MIN_MS = (__ENV.THINK_MIN ? parseFloat(__ENV.THINK_MIN) : 0.5) * 1000;
const THINK_MAX_MS = (__ENV.THINK_MAX ? parseFloat(__ENV.THINK_MAX) : 1.5) * 1000;

// --- Load profiles ---------------------------------------------------------
const profiles = {
  smoke: {
    executor: 'constant-vus',
    vus: 1,
    duration: '15s',
  },
  load: {
    executor: 'ramping-vus',
    startVUs: 0,
    stages: [
      { duration: RAMP_UP, target: TARGET_VUS },
      { duration: HOLD, target: TARGET_VUS },
      { duration: RAMP_DOWN, target: 0 },
    ],
    gracefulRampDown: '30s',
  },
  stress: {
    executor: 'ramping-vus',
    startVUs: 0,
    stages: [
      { duration: RAMP_UP, target: Math.max(1, Math.floor(TARGET_VUS * 0.25)) },
      { duration: HOLD, target: Math.max(1, Math.floor(TARGET_VUS * 0.25)) },
      { duration: RAMP_UP, target: Math.max(1, Math.floor(TARGET_VUS * 0.5)) },
      { duration: HOLD, target: Math.max(1, Math.floor(TARGET_VUS * 0.5)) },
      { duration: RAMP_UP, target: Math.max(1, Math.floor(TARGET_VUS * 0.75)) },
      { duration: HOLD, target: Math.max(1, Math.floor(TARGET_VUS * 0.75)) },
      { duration: RAMP_UP, target: TARGET_VUS },
      { duration: HOLD, target: TARGET_VUS },
      { duration: RAMP_DOWN, target: 0 },
    ],
    gracefulRampDown: '30s',
  },
};

const MODE_KEY = profiles[MODE] ? MODE : 'load';

// Full latency/booking budgets for load & stress runs.
const FULL_THRESHOLDS = {
  // Only *real* failures count here: by-design 404 (random non-existent id) and
  // 422 (quota full / duplicate) are whitelisted via setResponseCallback() below,
  // so they are NOT counted as http_req_failed.
  http_req_failed: ['rate<0.05'],
  http_req_duration: ['p(95)<2000', 'p(99)<5000'],
  checks: ['rate>0.95'],
  booking_errors: ['rate<0.05'],
  // Per-endpoint latency budgets (reads should stay snappy; writes get more room).
  'http_req_duration{name:ListSchedules}': ['p(95)<1500'],
  'http_req_duration{name:ScheduleDetail}': ['p(95)<1000'],
  'http_req_duration{name:ScheduleQuota}': ['p(95)<1000'],
  'http_req_duration{name:CreateBooking}': ['p(95)<2500'],
  'http_req_duration{name:CheckIn}': ['p(95)<2500'],
  'http_req_duration{name:Complete}': ['p(95)<2500'],
};

// Smoke is a pure connectivity/sanity check (1 VU): a single cold-start request
// should not trip a latency budget, so it only gates on failures + checks.
const SMOKE_THRESHOLDS = {
  http_req_failed: ['rate<0.05'],
  checks: ['rate>0.95'],
};

export const options = {
  scenarios: {
    [MODE_KEY]: profiles[MODE_KEY],
  },
  thresholds: MODE_KEY === 'smoke' ? SMOKE_THRESHOLDS : FULL_THRESHOLDS,
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
};

// 200 OK, 201 Created, 404 (random non-existent id) and 422 (quota full /
// duplicate) are all expected by design. Anything else (5xx, timeout, 429) is
// counted as a genuine failure in http_req_failed.
http.setResponseCallback(http.expectedStatuses(200, 201, 404, 422));

// --- Custom metrics --------------------------------------------------------
const bookingsCreated = new Counter('bookings_created');
const bookingsRejected = new Counter('bookings_rejected_422');
const bookingErrors = new Rate('booking_errors');
const e2eDuration = new Trend('booking_flow_duration', true);

const PARAMS = {
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  timeout: REQUEST_TIMEOUT,
};

function think() {
  sleep(randomIntBetween(THINK_MIN_MS, THINK_MAX_MS) / 1000);
}

// --- Connectivity / pre-flight check --------------------------------------
export function setup() {
  // Use a filtered list so the pre-flight check is cheap (the unfiltered list
  // returns the full ~10k-row dataset).
  const res = http.get(`${BASE_URL}/schedules?health_center_id=${HC_MIN}`, PARAMS);
  if (res.status !== 200) {
    throw new Error(
      `API not reachable at ${BASE_URL} (GET /schedules -> ${res.status}). ` +
      `Is the app up and seeded? Did you include /api/v1 in BASE_URL?`
    );
  }
  console.log(
    `[k6] mode=${MODE_KEY} target_vus=${TARGET_VUS} ` +
    `schedules=${SCHEDULE_MIN}-${SCHEDULE_MAX} patients=${PATIENT_MIN}-${PATIENT_MAX} ` +
    `health_centers=${HC_MIN}-${HC_MAX}`
  );
}

// --- Virtual-user journey --------------------------------------------------
export default function () {
  const scheduleId = randomIntBetween(SCHEDULE_MIN, SCHEDULE_MAX);
  const patientId = randomIntBetween(PATIENT_MIN, PATIENT_MAX);
  const startedAt = Date.now();

  // 1) Browse schedules (read-heavy path)
  group('browse_schedules', function () {
    // Filter by health_center_id (realistic "schedules at my clinic" query). This
    // exercises the schedules(health_center_id, date) index AND avoids the
    // unpaginated full-list response that GET /schedules with no filter returns
    // (~10k rows / several MB — a known app-side bottleneck, not an index issue).
    const healthCenterId = randomIntBetween(HC_MIN, HC_MAX);
    const list = http.get(`${BASE_URL}/schedules?health_center_id=${healthCenterId}`, Object.assign({ tags: { name: 'ListSchedules' } }, PARAMS));
    check(list, { 'GET /schedules (filtered) is 200': (r) => r.status === 200 });

    const detail = http.get(`${BASE_URL}/schedules/${scheduleId}`, Object.assign({ tags: { name: 'ScheduleDetail' } }, PARAMS));
    check(detail, { 'GET /schedules/{id} is 200/404': (r) => r.status === 200 || r.status === 404 });

    const quota = http.get(`${BASE_URL}/schedules/${scheduleId}/quota`, Object.assign({ tags: { name: 'ScheduleQuota' } }, PARAMS));
    check(quota, { 'GET /schedules/{id}/quota is 200/404': (r) => r.status === 200 || r.status === 404 });
  });

  think();

  // 2) Create booking (write path: duplicate check + quota lock + queue number)
  let bookingId = null;
  group('create_booking', function () {
    const payload = JSON.stringify({ schedule_id: scheduleId, patient_id: patientId });
    const res = http.post(`${BASE_URL}/bookings`, payload, Object.assign({ tags: { name: 'CreateBooking' } }, PARAMS));

    const ok = check(res, {
      'POST /bookings is 201 or 422 (expected)': (r) => r.status === 201 || r.status === 422,
      'POST /bookings has no server error': (r) => r.status < 500,
    });
    bookingErrors.add(!ok);

    if (res.status === 201) {
      bookingsCreated.add(1);
      bookingId = res.json('data.id');
    } else if (res.status === 422) {
      bookingsRejected.add(1);
    }
  });

  // 3) Booking lifecycle — only when a booking was actually created
  if (bookingId) {
    think();
    group('booking_lifecycle', function () {
      // Feed every lifecycle check into booking_errors so a write-path regression
      // (e.g. check-in / complete starting to 5xx) actually fails the run, instead
      // of being diluted away in the global checks rate.
      const detail = http.get(`${BASE_URL}/bookings/${bookingId}`, Object.assign({ tags: { name: 'BookingDetail' } }, PARAMS));
      bookingErrors.add(!check(detail, { 'GET /bookings/{id} is 200': (r) => r.status === 200 }));

      const list = http.get(`${BASE_URL}/bookings?schedule_id=${scheduleId}`, Object.assign({ tags: { name: 'ListBookings' } }, PARAMS));
      bookingErrors.add(!check(list, { 'GET /bookings?schedule_id is 200': (r) => r.status === 200 }));

      const checkIn = http.patch(`${BASE_URL}/bookings/${bookingId}/check-in`, null, Object.assign({ tags: { name: 'CheckIn' } }, PARAMS));
      bookingErrors.add(!check(checkIn, { 'PATCH check-in is 200': (r) => r.status === 200 }));

      think();

      const complete = http.patch(`${BASE_URL}/bookings/${bookingId}/complete`, null, Object.assign({ tags: { name: 'Complete' } }, PARAMS));
      bookingErrors.add(!check(complete, { 'PATCH complete is 200': (r) => r.status === 200 }));
    });
  }

  e2eDuration.add(Date.now() - startedAt);
  think();
}
