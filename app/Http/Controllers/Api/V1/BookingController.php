<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
    ) {}

    /**
     * List bookings filtered by schedule_id, patient_id, or status.
     *
     * GET /api/v1/bookings?schedule_id=1&status=pending&per_page=15
     * GET /api/v1/bookings?patient_id=1
     */
    public function index(Request $request): JsonResponse
    {
        // If patient_id is provided, return all bookings for that patient
        if ($request->has('patient_id')) {
            $bookings = $this->bookingService->getBookingsByPatient(
                (int) $request->input('patient_id')
            );

            return response()->json([
                'success' => true,
                'message' => 'Bookings retrieved successfully.',
                'data' => $bookings,
            ]);
        }

        // Otherwise, require schedule_id
        $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,checked_in,completed,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $bookings = $this->bookingService->getBookingsBySchedule(
            (int) $request->input('schedule_id'),
            $request->input('status'),
            (int) $request->input('per_page', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully.',
            'data' => $bookings,
        ]);
    }

    /**
     * Create a new booking.
     *
     * POST /api/v1/bookings
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->createBooking(
                $request->validated('schedule_id'),
                $request->validated('patient_id'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'data' => $booking,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Booking failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Get a single booking detail.
     *
     * GET /api/v1/bookings/{id}
     */
    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingService->getBookingDetail($id);

        return response()->json([
            'success' => true,
            'message' => 'Booking retrieved successfully.',
            'data' => $booking,
        ]);
    }

    /**
     * Check in a patient (mark attendance).
     *
     * PATCH /api/v1/bookings/{id}/check-in
     */
    public function checkIn(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->checkIn($id);

            return response()->json([
                'success' => true,
                'message' => 'Patient checked in successfully.',
                'data' => $booking,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Complete a booking (vaccination done).
     *
     * PATCH /api/v1/bookings/{id}/complete
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->completeBooking($id);

            return response()->json([
                'success' => true,
                'message' => 'Booking completed successfully.',
                'data' => $booking,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Completion failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Cancel a booking.
     *
     * PATCH /api/v1/bookings/{id}/cancel
     */
    public function cancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->cancelBooking(
                $id,
                $request->validated('cancellation_reason'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully.',
                'data' => $booking,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
