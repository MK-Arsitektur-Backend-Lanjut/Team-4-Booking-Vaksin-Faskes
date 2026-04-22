import http from 'k6/http';
import { check, sleep } from 'k6';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// Setup load test options
export const options = {
    stages: [
        { duration: '10s', target: 50 },  // Ramp up to 50 concurrent virtual users over 10 seconds
        { duration: '30s', target: 100 }, // Ramp up to 100 concurrent virtual users
        { duration: '1m', target: 100 },  // Stay at 100 users for 1 minute
        { duration: '20s', target: 0 },   // Ramp down to 0 users
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95% of requests must complete below 500ms
        http_req_failed: ['rate<0.1'],    // Error rate should be less than 10%
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000/api/v1';
const SCHEDULE_ID = __ENV.SCHEDULE_ID || 1;

export default function () {
    // Generate a random patient ID to simulate different users booking
    // Assuming patient IDs are between 1 and 100000
    const patientId = randomIntBetween(1, 100000);

    const payload = JSON.stringify({
        schedule_id: SCHEDULE_ID,
        patient_id: patientId,
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    };

    // 1. Test POST /bookings (Create Booking / Add to Queue)
    const bookingRes = http.post(`${BASE_URL}/bookings`, payload, params);

    // Verify response
    check(bookingRes, {
        'booking status is 201 (Success)': (r) => r.status === 201,
        'booking status is 422 (Quota Full / Duplicate)': (r) => r.status === 422,
    });

    // If booking was successful, test the Check-In and Complete endpoints
    if (bookingRes.status === 201) {
        const bookingId = bookingRes.json('data.id');

        // Simulate wait time before check-in
        sleep(randomIntBetween(1, 3));

        // 2. Test PATCH /bookings/{id}/check-in
        const checkInRes = http.patch(`${BASE_URL}/bookings/${bookingId}/check-in`, null, params);
        check(checkInRes, {
            'check-in status is 200': (r) => r.status === 200,
        });

        // Simulate wait time before completion
        sleep(randomIntBetween(2, 5));

        // 3. Test PATCH /bookings/{id}/complete
        const completeRes = http.patch(`${BASE_URL}/bookings/${bookingId}/complete`, null, params);
        check(completeRes, {
            'complete status is 200': (r) => r.status === 200,
        });
    }

    // Short sleep between iterations
    sleep(1);
}
