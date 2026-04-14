import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate } from 'k6/metrics';

const BASE_URL = __ENV.BASE_URL || 'http://host.docker.internal:8000';
const K6_PROFILE = (__ENV.K6_PROFILE || 'spike').toLowerCase();
const SLEEP_SECONDS = Number(__ENV.SLEEP_SECONDS || '1');
const REQUEST_TIMEOUT = __ENV.REQUEST_TIMEOUT || '30s';
const BURST10K_TARGET = Number(__ENV.BURST10K_TARGET || '10000');
const BURST10K_HOLD = __ENV.BURST10K_HOLD || '120s';
const BURST_RAMP_1 = __ENV.BURST_RAMP_1 || '30s';
const BURST_RAMP_2 = __ENV.BURST_RAMP_2 || '60s';
const BURST_RAMP_3 = __ENV.BURST_RAMP_3 || '90s';
const BURST_RAMP_DOWN_1 = __ENV.BURST_RAMP_DOWN_1 || '60s';
const BURST_RAMP_DOWN_2 = __ENV.BURST_RAMP_DOWN_2 || '30s';
const burstStage1Target = Math.max(1, Math.ceil(BURST10K_TARGET * 0.01));
const burstStage2Target = Math.max(1, Math.ceil(BURST10K_TARGET * 0.2));
const burstStage3Target = Math.max(1, Math.ceil(BURST10K_TARGET * 0.6));

const registerSuccessRate = new Rate('register_success_rate');
const registerRateLimitedRate = new Rate('register_rate_limited_rate');
const listSuccessRate = new Rate('list_success_rate');
const total429Counter = new Counter('http_429_total');

const spikeStages = [
  { duration: '30s', target: 20 },
  { duration: '60s', target: 500 },
  { duration: '90s', target: 2000 },
  { duration: '30s', target: 20 },
];

const burst10kStages = [
  { duration: BURST_RAMP_1, target: burstStage1Target },
  { duration: BURST_RAMP_2, target: burstStage2Target },
  { duration: BURST_RAMP_3, target: burstStage3Target },
  { duration: BURST10K_HOLD, target: BURST10K_TARGET },
  { duration: BURST_RAMP_DOWN_1, target: burstStage1Target },
  { duration: BURST_RAMP_DOWN_2, target: 1 },
];

const profileOptions = {
  smoke: {
      vus: 2,
      duration: '10s',
      thresholds: {
        http_req_failed: ['rate<0.05'],
        http_req_duration: ['p(95)<6000'],
        list_success_rate: ['rate>0.95'],
      },
    },
  spike: {
      scenarios: {
        patient_spike: {
          executor: 'ramping-vus',
          stages: spikeStages,
          gracefulRampDown: '30s',
        },
      },
      thresholds: {
        http_req_failed: ['rate<0.05'],
        http_req_duration: ['p(95)<5000'],
        list_success_rate: ['rate>0.90'],
      },
    },
  burst10k: {
      scenarios: {
        patient_burst_10k: {
          executor: 'ramping-vus',
          stages: burst10kStages,
          gracefulRampDown: '45s',
        },
      },
      thresholds: {
        http_req_failed: ['rate<0.10'],
        http_req_duration: ['p(95)<8000'],
        list_success_rate: ['rate>0.80'],
      },
    },
};

export const options = profileOptions[K6_PROFILE] || profileOptions.spike;

function generateNik() {
  const raw = `${Date.now()}${__VU}${__ITER}`.replace(/\D/g, '');
  return raw.padEnd(16, '7').slice(0, 16);
}

function generatePhoneNumber() {
  const raw = `08${String(__VU).padStart(4, '0')}${String(__ITER).padStart(9, '0')}`;
  return raw.slice(0, 20);
}

export default function () {
  const listRes = http.get(`${BASE_URL}/api/v1/patients?per_page=15&page=1`, {
    timeout: REQUEST_TIMEOUT,
  });
  const listOk = check(listRes, {
    'patients list status is 200': (r) => r.status === 200,
  });
  listSuccessRate.add(listOk);

  if (listRes.status === 429) {
    total429Counter.add(1);
  }

  const payload = JSON.stringify({
    nik: generateNik(),
    full_name: `Load Test User ${__VU}-${__ITER}`,
    birth_date: '1999-01-01',
    gender: __ITER % 2 === 0 ? 'male' : 'female',
    phone_number: generatePhoneNumber(),
    address: 'Alamat load test',
  });

  const registerRes = http.post(`${BASE_URL}/api/v1/patients`, payload, {
    headers: {
      'Content-Type': 'application/json',
    },
    timeout: REQUEST_TIMEOUT,
  });

  const registerOk = check(registerRes, {
    'register status is 201/422/429': (r) => [201, 422, 429].includes(r.status),
  });

  registerSuccessRate.add(registerOk);

  if (registerRes.status === 429) {
    total429Counter.add(1);
    registerRateLimitedRate.add(true);
  } else {
    registerRateLimitedRate.add(false);
  }

  sleep(SLEEP_SECONDS);
}
