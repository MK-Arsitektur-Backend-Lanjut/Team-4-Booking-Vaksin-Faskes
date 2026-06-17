/**
 * k6 Stress Test — Module 1: Health Center & Schedule
 *
 * Tests all Module 1 endpoints under load:
 * 1. Health Centers: list, search, filter by province/city, get active
 * 2. Vaccines: list, search, get active
 * 3. Vaccine Stocks: list, get available, filter by health center
 * 4. Vaccine Schedules: list, get available, filter by date/health center
 *
 * Profiles:
 *   - smoke    : 2 VUs, 10s — cek konektivitas
 *   - spike    : 20 → 500 → 2000 VUs — simulasi lonjakan
 *   - burst10k : ramp up ke 10.000 VUs — simulasi kuota baru dibuka
 *
 * Usage:
 *   docker run --rm -v ${PWD}:/scripts \
 *     -e BASE_URL=http://host.docker.internal:8000 \
 *     -e K6_PROFILE=smoke \
 *     grafana/k6 run /scripts/loadtest/k6-module1-stress.js
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// ─── Configuration ──────────────────────────────────────────────
const BASE_URL       = __ENV.BASE_URL || 'http://host.docker.internal:8000';
const API_PREFIX     = `${BASE_URL}/api`;
const K6_PROFILE     = (__ENV.K6_PROFILE || 'spike').toLowerCase();
const SLEEP_SECONDS  = Number(__ENV.SLEEP_SECONDS || '0.5');
const REQUEST_TIMEOUT = __ENV.REQUEST_TIMEOUT || '30s';

// Burst10k configurable params
const BURST10K_TARGET    = Number(__ENV.BURST10K_TARGET || '10000');
const BURST10K_HOLD      = __ENV.BURST10K_HOLD || '120s';
const BURST_RAMP_1       = __ENV.BURST_RAMP_1 || '30s';
const BURST_RAMP_2       = __ENV.BURST_RAMP_2 || '60s';
const BURST_RAMP_3       = __ENV.BURST_RAMP_3 || '90s';
const BURST_RAMP_DOWN_1  = __ENV.BURST_RAMP_DOWN_1 || '60s';
const BURST_RAMP_DOWN_2  = __ENV.BURST_RAMP_DOWN_2 || '30s';

// ─── Custom Metrics ─────────────────────────────────────────────
const healthCenterListSuccess  = new Rate('hc_list_success');
const healthCenterSearchSuccess = new Rate('hc_search_success');
const vaccineListSuccess       = new Rate('vaccine_list_success');
const stockAvailableSuccess    = new Rate('stock_available_success');
const scheduleAvailableSuccess = new Rate('schedule_available_success');
const total429Counter          = new Counter('http_429_total');
const endpointDuration         = new Trend('endpoint_duration', true);

// ─── Stages ─────────────────────────────────────────────────────
const spikeStages = [
  { duration: '30s',  target: 20 },     // warm up
  { duration: '60s',  target: 500 },    // ramp up
  { duration: '90s',  target: 2000 },   // spike peak
  { duration: '30s',  target: 20 },     // cool down
];

const burst10kStages = [
  { duration: BURST_RAMP_1,     target: Math.max(1, Math.ceil(BURST10K_TARGET * 0.01)) },
  { duration: BURST_RAMP_2,     target: Math.max(1, Math.ceil(BURST10K_TARGET * 0.2)) },
  { duration: BURST_RAMP_3,     target: Math.max(1, Math.ceil(BURST10K_TARGET * 0.6)) },
  { duration: BURST10K_HOLD,    target: BURST10K_TARGET },
  { duration: BURST_RAMP_DOWN_1, target: Math.max(1, Math.ceil(BURST10K_TARGET * 0.01)) },
  { duration: BURST_RAMP_DOWN_2, target: 1 },
];

// ─── Profile Options ────────────────────────────────────────────
const profileOptions = {
  smoke: {
    vus: 2,
    duration: '10s',
    thresholds: {
      http_req_failed:          ['rate<0.05'],
      http_req_duration:        ['p(95)<5000'],
      hc_list_success:          ['rate>0.95'],
      schedule_available_success: ['rate>0.95'],
    },
  },
  spike: {
    scenarios: {
      module1_spike: {
        executor: 'ramping-vus',
        stages: spikeStages,
        gracefulRampDown: '30s',
      },
    },
    thresholds: {
      http_req_failed:          ['rate<0.05'],
      http_req_duration:        ['p(95)<5000'],
      hc_list_success:          ['rate>0.90'],
      schedule_available_success: ['rate>0.90'],
    },
  },
  burst10k: {
    scenarios: {
      module1_burst: {
        executor: 'ramping-vus',
        stages: burst10kStages,
        gracefulRampDown: '45s',
      },
    },
    thresholds: {
      http_req_failed:          ['rate<0.10'],
      http_req_duration:        ['p(95)<8000'],
      hc_list_success:          ['rate>0.80'],
      schedule_available_success: ['rate>0.80'],
    },
  },
};

export const options = profileOptions[K6_PROFILE] || profileOptions.spike;

// ─── Test Data ──────────────────────────────────────────────────
const PROVINCES = ['JAKARTA', 'JAWA BARAT', 'JAWA TENGAH', 'JAWA TIMUR', 'BALI'];
const CITIES = ['JAKARTA PUSAT', 'BANDUNG', 'SEMARANG', 'SURABAYA', 'DENPASAR'];
const SEARCH_TERMS = ['Puskesmas', 'Klinik', 'RS Umum', 'Vaksinasi', 'Pratama'];

function randomFrom(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function randomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function getParams() {
  return { timeout: REQUEST_TIMEOUT };
}

function track429(res) {
  if (res.status === 429) {
    total429Counter.add(1);
  }
}

// ─── Main Test Function ─────────────────────────────────────────
export default function () {
  // Randomly pick one of the test scenarios per iteration
  // This simulates realistic mixed traffic
  const scenario = randomInt(1, 10);

  if (scenario <= 3) {
    testHealthCenters();
  } else if (scenario <= 5) {
    testVaccines();
  } else if (scenario <= 7) {
    testVaccineStocks();
  } else {
    testVaccineSchedules();
  }

  sleep(SLEEP_SECONDS);
}

// ─── Test Groups ────────────────────────────────────────────────

function testHealthCenters() {
  group('Health Centers', function () {

    // 1. List health centers (paginated)
    const listRes = http.get(`${API_PREFIX}/health-centers?per_page=15`, getParams());
    const listOk = check(listRes, {
      'HC list: status 200': (r) => r.status === 200,
      'HC list: has data': (r) => {
        try { return JSON.parse(r.body).data !== undefined; } catch { return false; }
      },
    });
    healthCenterListSuccess.add(listOk);
    endpointDuration.add(listRes.timings.duration);
    track429(listRes);

    // 2. Search health centers
    const searchTerm = randomFrom(SEARCH_TERMS);
    const searchRes = http.get(`${API_PREFIX}/health-centers/search?q=${searchTerm}`, getParams());
    const searchOk = check(searchRes, {
      'HC search: status 200': (r) => r.status === 200,
    });
    healthCenterSearchSuccess.add(searchOk);
    endpointDuration.add(searchRes.timings.duration);
    track429(searchRes);

    // 3. Get active health centers
    const activeRes = http.get(`${API_PREFIX}/health-centers/active`, getParams());
    check(activeRes, {
      'HC active: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(activeRes.timings.duration);
    track429(activeRes);

    // 4. Filter by province
    const province = randomFrom(PROVINCES);
    const provRes = http.get(`${API_PREFIX}/health-centers/by-province/${encodeURIComponent(province)}`, getParams());
    check(provRes, {
      'HC by province: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(provRes.timings.duration);
    track429(provRes);

    // 5. Filter by city
    const city = randomFrom(CITIES);
    const cityRes = http.get(`${API_PREFIX}/health-centers/by-city/${encodeURIComponent(city)}`, getParams());
    check(cityRes, {
      'HC by city: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(cityRes.timings.duration);
    track429(cityRes);

    // 6. Get single health center by ID
    const hcId = randomInt(1, 100);
    const showRes = http.get(`${API_PREFIX}/health-centers/${hcId}`, getParams());
    check(showRes, {
      'HC show: status 200 or 404': (r) => [200, 404].includes(r.status),
    });
    endpointDuration.add(showRes.timings.duration);
    track429(showRes);
  });
}

function testVaccines() {
  group('Vaccines', function () {

    // 1. List vaccines
    const listRes = http.get(`${API_PREFIX}/vaccines?per_page=15`, getParams());
    const listOk = check(listRes, {
      'Vaccine list: status 200': (r) => r.status === 200,
    });
    vaccineListSuccess.add(listOk);
    endpointDuration.add(listRes.timings.duration);
    track429(listRes);

    // 2. Get active vaccines
    const activeRes = http.get(`${API_PREFIX}/vaccines/active`, getParams());
    check(activeRes, {
      'Vaccine active: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(activeRes.timings.duration);
    track429(activeRes);

    // 3. Search vaccines
    const searchTerms = ['Pfizer', 'Moderna', 'Sinovac', 'AstraZeneca', 'Polio'];
    const searchRes = http.get(`${API_PREFIX}/vaccines/search?q=${randomFrom(searchTerms)}`, getParams());
    check(searchRes, {
      'Vaccine search: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(searchRes.timings.duration);
    track429(searchRes);

    // 4. Show single vaccine
    const vacId = randomInt(1, 13);
    const showRes = http.get(`${API_PREFIX}/vaccines/${vacId}`, getParams());
    check(showRes, {
      'Vaccine show: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(showRes.timings.duration);
    track429(showRes);
  });
}

function testVaccineStocks() {
  group('Vaccine Stocks', function () {

    // 1. List vaccine stocks
    const listRes = http.get(`${API_PREFIX}/vaccine-stocks?per_page=15`, getParams());
    const listOk = check(listRes, {
      'Stock list: status 200': (r) => r.status === 200,
    });
    stockAvailableSuccess.add(listOk);
    endpointDuration.add(listRes.timings.duration);
    track429(listRes);

    // 2. Get available stocks
    const availRes = http.get(`${API_PREFIX}/vaccine-stocks/available`, getParams());
    check(availRes, {
      'Stock available: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(availRes.timings.duration);
    track429(availRes);

    // 3. Get stocks by health center
    const hcId = randomInt(1, 100);
    const hcRes = http.get(`${API_PREFIX}/vaccine-stocks/health-center/${hcId}`, getParams());
    check(hcRes, {
      'Stock by HC: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(hcRes.timings.duration);
    track429(hcRes);
  });
}

function testVaccineSchedules() {
  group('Vaccine Schedules', function () {

    // 1. List vaccine schedules
    const listRes = http.get(`${API_PREFIX}/vaccine-schedules?per_page=15`, getParams());
    const listOk = check(listRes, {
      'Schedule list: status 200': (r) => r.status === 200,
    });
    scheduleAvailableSuccess.add(listOk);
    endpointDuration.add(listRes.timings.duration);
    track429(listRes);

    // 2. Get available schedules (paling kritis — endpoint ini dipanggil saat kuota baru dibuka)
    const availRes = http.get(`${API_PREFIX}/vaccine-schedules/available`, getParams());
    check(availRes, {
      'Schedule available: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(availRes.timings.duration);
    track429(availRes);

    // 3. Get schedules by date (tomorrow)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dateStr = tomorrow.toISOString().split('T')[0];
    const dateRes = http.get(`${API_PREFIX}/vaccine-schedules/by-date?date=${dateStr}`, getParams());
    check(dateRes, {
      'Schedule by date: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(dateRes.timings.duration);
    track429(dateRes);

    // 4. Get schedules by health center
    const hcId = randomInt(1, 100);
    const hcRes = http.get(`${API_PREFIX}/vaccine-schedules/health-center/${hcId}`, getParams());
    check(hcRes, {
      'Schedule by HC: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(hcRes.timings.duration);
    track429(hcRes);

    // 5. Get schedules by health center + date range
    const start = new Date();
    start.setDate(start.getDate() + 1);
    const end = new Date();
    end.setDate(end.getDate() + 3);
    const startStr = start.toISOString().split('T')[0];
    const endStr = end.toISOString().split('T')[0];
    const rangeRes = http.get(
      `${API_PREFIX}/vaccine-schedules/by-date-range?health_center_id=${hcId}&start_date=${startStr}&end_date=${endStr}`,
      getParams()
    );
    check(rangeRes, {
      'Schedule by range: status 200': (r) => r.status === 200,
    });
    endpointDuration.add(rangeRes.timings.duration);
    track429(rangeRes);
  });
}
