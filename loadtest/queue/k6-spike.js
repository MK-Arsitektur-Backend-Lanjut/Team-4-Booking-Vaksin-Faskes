import http from 'k6/http';
import { check, sleep } from 'k6';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// ---------------------------------------------------------
// Environment Variables Configuration
// ---------------------------------------------------------
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000/api/v1';
const SCHEDULE_ID = __ENV.SCHEDULE_ID || 1;
const PROFILE = __ENV.K6_PROFILE || 'smoke';

// Burst 10k Configuration
const BURST10K_TARGET = __ENV.BURST10K_TARGET ? parseInt(__ENV.BURST10K_TARGET) : 10000;
const BURST10K_HOLD = __ENV.BURST10K_HOLD || '120s';

// Custom Ramps for Burst10k
const BURST_RAMP_1 = __ENV.BURST_RAMP_1 || '30s';
const BURST_RAMP_2 = __ENV.BURST_RAMP_2 || '1m';
const BURST_RAMP_3 = __ENV.BURST_RAMP_3 || '30s';
const BURST_RAMP_DOWN_1 = __ENV.BURST_RAMP_DOWN_1 || '30s';
const BURST_RAMP_DOWN_2 = __ENV.BURST_RAMP_DOWN_2 || '30s';

// ---------------------------------------------------------
// Load Profiles
// ---------------------------------------------------------
const profiles = {
    smoke: {
        vus: 1,
        duration: '10s',
    },
    spike: {
        stages: [
            { duration: '10s', target: 100 }, // Fast ramp-up
            { duration: '1m', target: 100 },  // Hold spike
            { duration: '10s', target: 0 },   // Fast ramp-down
        ],
    },
    burst10k: {
        stages: [
            { duration: BURST_RAMP_1, target: Math.floor(BURST10K_TARGET * 0.3) },
            { duration: BURST_RAMP_2, target: Math.floor(BURST10K_TARGET * 0.7) },
            { duration: BURST_RAMP_3, target: BURST10K_TARGET },
            { duration: BURST10K_HOLD, target: BURST10K_TARGET }, // Hold peak
            { duration: BURST_RAMP_DOWN_1, target: Math.floor(BURST10K_TARGET * 0.3) },
            { duration: BURST_RAMP_DOWN_2, target: 0 },
        ],
    }
};

export const options = profiles[PROFILE] || profiles['smoke'];

// Tambahkan Thresholds
options.thresholds = {
    http_req_duration: ['p(95)<2000'], // Toleransi lebih tinggi saat load besar
    http_req_failed: ['rate<0.1'],     // Gagal di bawah 10%
};

// ---------------------------------------------------------
// Test Scenario (E2E All Endpoints)
// ---------------------------------------------------------
export default function () {
    const patientId = randomIntBetween(1, 1000000);

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        timeout: __ENV.REQUEST_TIMEOUT || '30s',
    };

    // --- 1. ENDPOINT SCHEDULES ---
    
    // GET /schedules
    const getSchedulesRes = http.get(`${BASE_URL}/schedules`, params);
    check(getSchedulesRes, { 'GET /schedules is 200': (r) => r.status === 200 });

    // GET /schedules/{id}
    const getScheduleRes = http.get(`${BASE_URL}/schedules/${SCHEDULE_ID}`, params);
    check(getScheduleRes, { 'GET /schedules/{id} is 200': (r) => r.status === 200 });

    // GET /schedules/{id}/quota
    const getQuotaRes = http.get(`${BASE_URL}/schedules/${SCHEDULE_ID}/quota`, params);
    check(getQuotaRes, { 'GET /schedules/{id}/quota is 200': (r) => r.status === 200 });


    // --- 2. ENDPOINT BOOKINGS (POST) ---
    
    const payload = JSON.stringify({
        schedule_id: SCHEDULE_ID,
        patient_id: patientId,
    });
    
    const bookingRes = http.post(`${BASE_URL}/bookings`, payload, params);
    check(bookingRes, {
        'POST /bookings is 201 (Success)': (r) => r.status === 201,
        'POST /bookings is 422 (Quota Full/Dup)': (r) => r.status === 422,
        'POST /bookings is 500+ (Error)': (r) => r.status >= 500,
    });


    // --- 3. ENDPOINT BOOKINGS (Lanjutan jika berhasil) ---
    
    if (bookingRes.status === 201) {
        const bookingId = bookingRes.json('data.id');

        // GET /bookings/{id} (Detail Booking)
        const getBookingRes = http.get(`${BASE_URL}/bookings/${bookingId}`, params);
        check(getBookingRes, { 'GET /bookings/{id} is 200': (r) => r.status === 200 });

        // GET /bookings (List Booking)
        const getBookingsRes = http.get(`${BASE_URL}/bookings?schedule_id=${SCHEDULE_ID}`, params);
        check(getBookingsRes, { 'GET /bookings is 200': (r) => r.status === 200 });

        // Simulasi jeda pasien menunggu dipanggil
        sleep(0.5);

        // PATCH /bookings/{id}/check-in
        const checkInRes = http.patch(`${BASE_URL}/bookings/${bookingId}/check-in`, null, params);
        check(checkInRes, { 'PATCH check-in is 200': (r) => r.status === 200 });

        // Simulasi jeda pasien disuntik vaksin
        sleep(0.5);

        // PATCH /bookings/{id}/complete
        const completeRes = http.patch(`${BASE_URL}/bookings/${bookingId}/complete`, null, params);
        check(completeRes, { 'PATCH complete is 200': (r) => r.status === 200 });
        
        // (Opsional) Kita tidak mengetes Cancel di flow yang sukses ini,
        // tapi endpoint cancel biasanya dites di skenario terpisah / gagal.
    }

    // Istirahat sebentar di akhir flow setiap user
    sleep(1);
}
