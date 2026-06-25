import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.1.0/index.js';

// ─────────────────────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || 'http://host.docker.internal:8000';
const K6_PROFILE = (__ENV.K6_PROFILE || 'smoke').toLowerCase();
const SLEEP_MIN = Number(__ENV.SLEEP_MIN || '0.5');
const SLEEP_MAX = Number(__ENV.SLEEP_MAX || '1.5');
const REQUEST_TIMEOUT = __ENV.REQUEST_TIMEOUT || '30s';

// ─────────────────────────────────────────────────────────────
// Custom Metrics
// ─────────────────────────────────────────────────────────────

// Per-endpoint duration tracking
const registerDuration = new Trend('register_duration', true);
const searchDuration = new Trend('search_duration', true);
const showPatientDuration = new Trend('show_patient_duration', true);
const verifyDuration = new Trend('verify_duration', true);
const healthHistoryReadDuration = new Trend('health_history_read_duration', true);
const healthHistoryWriteDuration = new Trend('health_history_write_duration', true);
const vaccHistoryReadDuration = new Trend('vacc_history_read_duration', true);
const vaccHistoryWriteDuration = new Trend('vacc_history_write_duration', true);

// Success rates
const registerSuccessRate = new Rate('register_success_rate');
const searchSuccessRate = new Rate('search_success_rate');
const showPatientSuccessRate = new Rate('show_patient_success_rate');
const verifySuccessRate = new Rate('verify_success_rate');
const healthHistorySuccessRate = new Rate('health_history_success_rate');
const vaccHistorySuccessRate = new Rate('vacc_history_success_rate');

// Error counters
const serverErrorCounter = new Counter('http_5xx_total');
const rateLimitCounter = new Counter('http_429_total');
const rateLimitedRate = new Rate('rate_limited_rate');

// ─────────────────────────────────────────────────────────────
// Test Profiles
// ─────────────────────────────────────────────────────────────
const profileOptions = {
  smoke: {
    scenarios: {
      patient_smoke: {
        executor: 'constant-vus',
        vus: 2,
        duration: '30s',
      },
    },
    thresholds: {
      http_req_failed: ['rate<0.01'],
      http_req_duration: ['p(95)<2000'],
      register_success_rate: ['rate>0.95'],
      search_success_rate: ['rate>0.95'],
    },
  },
  load: {
    scenarios: {
      patient_load: {
        executor: 'ramping-vus',
        stages: [
          { duration: '30s', target: 20 },
          { duration: '1m', target: 50 },
          { duration: '2m', target: 100 },
          { duration: '1m', target: 50 },
          { duration: '30s', target: 10 },
        ],
        gracefulRampDown: '30s',
      },
    },
    thresholds: {
      http_req_failed: ['rate<0.02'],
      http_req_duration: ['p(95)<3000'],
      register_success_rate: ['rate>0.90'],
      search_success_rate: ['rate>0.90'],
    },
  },
  stress: {
    scenarios: {
      patient_stress: {
        executor: 'ramping-vus',
        stages: [
          { duration: '30s', target: 50 },
          { duration: '1m', target: 200 },
          { duration: '2m', target: 500 },
          { duration: '3m', target: 500 },
          { duration: '1m', target: 200 },
          { duration: '30s', target: 10 },
        ],
        gracefulRampDown: '30s',
      },
    },
    thresholds: {
      http_req_failed: ['rate<0.05'],
      http_req_duration: ['p(95)<5000'],
      register_success_rate: ['rate>0.80'],
      search_success_rate: ['rate>0.80'],
    },
  },
  spike: {
    scenarios: {
      patient_spike: {
        executor: 'ramping-vus',
        stages: [
          { duration: '20s', target: 20 },
          { duration: '10s', target: 2000 },   // rapid spike
          { duration: '1m', target: 2000 },     // hold peak
          { duration: '20s', target: 50 },      // rapid drop
          { duration: '1m', target: 50 },       // recovery
          { duration: '10s', target: 0 },
        ],
        gracefulRampDown: '30s',
      },
    },
    thresholds: {
      http_req_failed: ['rate<0.10'],
      http_req_duration: ['p(95)<8000'],
      register_success_rate: ['rate>0.70'],
      search_success_rate: ['rate>0.70'],
    },
  },
};

export const options = profileOptions[K6_PROFILE] || profileOptions.smoke;

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────
function generateUniqueNik() {
  // Combine timestamp + VU ID + iteration to guarantee uniqueness across VUs
  const ts = Date.now().toString().slice(-10);
  const vu = String(__VU).padStart(3, '0');
  const iter = String(__ITER).padStart(3, '0');
  return (ts + vu + iter).slice(0, 16).padEnd(16, '0');
}

function randomSleep() {
  sleep(SLEEP_MIN + Math.random() * (SLEEP_MAX - SLEEP_MIN));
}

function trackErrors(res) {
  if (res.status >= 500) {
    serverErrorCounter.add(1);
  }
  if (res.status === 429) {
    rateLimitCounter.add(1);
    rateLimitedRate.add(true);
  } else {
    rateLimitedRate.add(false);
  }
}

const jsonHeaders = { 'Content-Type': 'application/json' };
const reqParams = { headers: jsonHeaders, timeout: REQUEST_TIMEOUT };
const getParams = { timeout: REQUEST_TIMEOUT };

// ─────────────────────────────────────────────────────────────
// Main Scenario
// ─────────────────────────────────────────────────────────────
export default function () {
  let createdPatientId = null;
  let createdHealthHistoryId = null;
  let createdVaccHistoryId = null;

  // ── 1. Register Patient ────────────────────────────────────
  group('01 - Register Patient', () => {
    const nik = generateUniqueNik();
    const payload = JSON.stringify({
      nik: nik,
      full_name: `StressTest User ${__VU}-${__ITER}`,
      birth_date: '1995-06-15',
      gender: __ITER % 2 === 0 ? 'male' : 'female',
      phone_number: `08${String(__VU).padStart(4, '0')}${String(__ITER).padStart(8, '0')}`.slice(0, 20),
      address: `Jl. Load Test No. ${__VU}, Kota ${__ITER}`,
    });

    const res = http.post(`${BASE_URL}/api/v1/patients`, payload, reqParams);
    registerDuration.add(res.timings.duration);
    trackErrors(res);

    const ok = check(res, {
      'register: status 201 or 422 (dup NIK)': (r) => [201, 422].includes(r.status),
    });
    registerSuccessRate.add(ok);

    if (res.status === 201) {
      try {
        const body = JSON.parse(res.body);
        createdPatientId = body.data?.patient_id || null;
      } catch (_) { /* ignore parse errors */ }
    }
  });

  randomSleep();

  // ── 2. Search / List Patients ──────────────────────────────
  group('02 - Search Patients', () => {
    const searchTerms = ['Stress', `${__VU}`, ''];
    const term = searchTerms[__ITER % searchTerms.length];
    const url = `${BASE_URL}/api/v1/patients?per_page=15&page=1${term ? `&search=${encodeURIComponent(term)}` : ''}`;

    const res = http.get(url, getParams);
    searchDuration.add(res.timings.duration);
    trackErrors(res);

    const ok = check(res, {
      'search: status 200': (r) => r.status === 200,
    });
    searchSuccessRate.add(ok);
  });

  randomSleep();

  // ── 3. Show Patient by ID ─────────────────────────────────
  if (createdPatientId) {
    group('03 - Show Patient', () => {
      const res = http.get(`${BASE_URL}/api/v1/patients/${createdPatientId}`, getParams);
      showPatientDuration.add(res.timings.duration);
      trackErrors(res);

      const ok = check(res, {
        'show: status 200': (r) => r.status === 200,
      });
      showPatientSuccessRate.add(ok);
    });

    randomSleep();
  }

  // ── 4. Verify Identity ────────────────────────────────────
  group('04 - Verify Identity', () => {
    // Use the NIK we just registered (regenerate same pattern)
    const ts = Date.now().toString().slice(-10);
    const vu = String(__VU).padStart(3, '0');
    const iter = String(__ITER).padStart(3, '0');
    const nik = (ts + vu + iter).slice(0, 16).padEnd(16, '0');

    const payload = JSON.stringify({
      nik: nik,
      birth_date: '1995-06-15',
    });

    const res = http.post(`${BASE_URL}/api/v1/patients/verify-identity`, payload, reqParams);
    verifyDuration.add(res.timings.duration);
    trackErrors(res);

    const ok = check(res, {
      'verify: status 200 or 422 (not found)': (r) => [200, 422].includes(r.status),
    });
    verifySuccessRate.add(ok);
  });

  randomSleep();

  // ── 5. Health Histories CRUD ──────────────────────────────
  if (createdPatientId) {
    group('05 - Health History CRUD', () => {
      // 5a. List health histories
      const listRes = http.get(
        `${BASE_URL}/api/v1/patients/${createdPatientId}/health-histories`,
        getParams
      );
      healthHistoryReadDuration.add(listRes.timings.duration);
      trackErrors(listRes);

      check(listRes, {
        'health-hist list: status 200': (r) => r.status === 200,
      });

      // 5b. Add health history
      const addPayload = JSON.stringify({
        condition_name: `Condition-${__VU}-${__ITER}`,
        diagnosed_at: '2024-01-15',
        notes: 'Stress test health history entry',
      });

      const addRes = http.post(
        `${BASE_URL}/api/v1/patients/${createdPatientId}/health-histories`,
        addPayload,
        reqParams
      );
      healthHistoryWriteDuration.add(addRes.timings.duration);
      trackErrors(addRes);

      const addOk = check(addRes, {
        'health-hist add: status 201': (r) => r.status === 201,
      });
      healthHistorySuccessRate.add(addOk);

      if (addRes.status === 201) {
        try {
          const body = JSON.parse(addRes.body);
          createdHealthHistoryId = body.data?.health_history_id || null;
        } catch (_) { /* ignore */ }
      }

      // 5c. Update health history (if created)
      if (createdHealthHistoryId) {
        const updatePayload = JSON.stringify({
          condition_name: `Updated-Condition-${__VU}`,
          notes: 'Updated during stress test',
        });

        const updateRes = http.put(
          `${BASE_URL}/api/v1/patients/${createdPatientId}/health-histories/${createdHealthHistoryId}`,
          updatePayload,
          reqParams
        );
        healthHistoryWriteDuration.add(updateRes.timings.duration);
        trackErrors(updateRes);

        check(updateRes, {
          'health-hist update: status 200': (r) => r.status === 200,
        });

        // 5d. Delete health history
        const deleteRes = http.del(
          `${BASE_URL}/api/v1/patients/${createdPatientId}/health-histories/${createdHealthHistoryId}`,
          null,
          getParams
        );
        healthHistoryWriteDuration.add(deleteRes.timings.duration);
        trackErrors(deleteRes);

        check(deleteRes, {
          'health-hist delete: status 200': (r) => r.status === 200,
        });
      }
    });

    randomSleep();

    // ── 6. Vaccination Histories CRUD ─────────────────────────
    group('06 - Vaccination History CRUD', () => {
      // 6a. List vaccination histories
      const listRes = http.get(
        `${BASE_URL}/api/v1/patients/${createdPatientId}/vaccination-histories`,
        getParams
      );
      vaccHistoryReadDuration.add(listRes.timings.duration);
      trackErrors(listRes);

      check(listRes, {
        'vacc-hist list: status 200': (r) => r.status === 200,
      });

      // 6b. Add vaccination history
      const addPayload = JSON.stringify({
        vaccine_name: `COVID-19 Booster ${__ITER}`,
        dose_number: (__ITER % 4) + 1,
        vaccinated_at: '2024-03-20T10:00:00',
        provider_name: `RS Stress Test ${__VU}`,
        notes: 'Stress test vaccination entry',
      });

      const addRes = http.post(
        `${BASE_URL}/api/v1/patients/${createdPatientId}/vaccination-histories`,
        addPayload,
        reqParams
      );
      vaccHistoryWriteDuration.add(addRes.timings.duration);
      trackErrors(addRes);

      const addOk = check(addRes, {
        'vacc-hist add: status 201': (r) => r.status === 201,
      });
      vaccHistorySuccessRate.add(addOk);

      if (addRes.status === 201) {
        try {
          const body = JSON.parse(addRes.body);
          createdVaccHistoryId = body.data?.vaccination_history_id || null;
        } catch (_) { /* ignore */ }
      }

      // 6c. Update vaccination history (if created)
      if (createdVaccHistoryId) {
        const updatePayload = JSON.stringify({
          vaccine_name: `Updated Vaccine ${__VU}`,
          dose_number: 2,
          vaccinated_at: '2024-06-15T14:00:00',
          notes: 'Updated during stress test',
        });

        const updateRes = http.put(
          `${BASE_URL}/api/v1/patients/${createdPatientId}/vaccination-histories/${createdVaccHistoryId}`,
          updatePayload,
          reqParams
        );
        vaccHistoryWriteDuration.add(updateRes.timings.duration);
        trackErrors(updateRes);

        check(updateRes, {
          'vacc-hist update: status 200': (r) => r.status === 200,
        });

        // 6d. Delete vaccination history
        const deleteRes = http.del(
          `${BASE_URL}/api/v1/patients/${createdPatientId}/vaccination-histories/${createdVaccHistoryId}`,
          null,
          getParams
        );
        vaccHistoryWriteDuration.add(deleteRes.timings.duration);
        trackErrors(deleteRes);

        check(deleteRes, {
          'vacc-hist delete: status 200': (r) => r.status === 200,
        });
      }
    });
  }

  randomSleep();
}

// ─────────────────────────────────────────────────────────────
// Summary Export
// ─────────────────────────────────────────────────────────────
export function handleSummary(data) {
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  const profile = K6_PROFILE;

  return {
    stdout: textSummary(data, { indent: '  ', enableColors: true }),
    [`results/summary_${profile}_${timestamp}.json`]: JSON.stringify(data, null, 2),
  };
}
