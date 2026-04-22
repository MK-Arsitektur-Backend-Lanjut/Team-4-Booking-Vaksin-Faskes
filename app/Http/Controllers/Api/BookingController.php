<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
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
            $patientId = (int) $request->input('patient_id');
            $bookings = $this->bookingRepository->findByPatient($patientId);

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

        $scheduleId = (int) $request->input('schedule_id');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 15);

        $bookings = $this->bookingRepository->findBySchedule($scheduleId, $status, $perPage);

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
        $scheduleId = $request->validated('schedule_id');
        $patientId = $request->validated('patient_id');

        // Check for duplicate booking
        if ($this->bookingRepository->checkDuplicateBooking($scheduleId, $patientId)) {
            return response()->json([
                'success' => false,
                'message' => 'Booking failed.',
                'errors' => ['patient_id' => ['Patient already has an active booking for this schedule.']],
            ], 422);
        }

        // Check quota availability
        $quota = $this->bookingRepository->getQuotaUsage($scheduleId);
        if ($quota['available'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Booking failed.',
                'errors' => ['schedule_id' => ['No available quota for this schedule. All slots are fully booked.']],
            ], 422);
        }

        // Get the next queue number
        $queueNumber = $this->bookingRepository->getNextQueueNumber($scheduleId);

        // Create the booking
        $booking = $this->bookingRepository->create([
            'schedule_id' => $scheduleId,
            'patient_id' => $patientId,
            'queue_number' => $queueNumber,
            'status' => 'pending',
            'booked_at' => Carbon::now(),
        ]);

        $booking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => $booking,
        ], 201);
    }

    /**
     * Get a single booking detail.
     *
     * GET /api/v1/bookings/{id}
     */
    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->findOrFail($id);
        $booking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);

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
        $booking = $this->bookingRepository->findOrFail($id);

        if ($booking->status !== 'pending' && $booking->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Check-in failed.',
                'errors' => ['status' => ["Cannot check in a booking with status '{$booking->status}'. Must be 'pending' or 'confirmed'."]],
            ], 422);
        }

        $this->bookingRepository->updateStatus($id, 'checked_in', [
            'checked_in_at' => Carbon::now(),
        ]);

        $updatedBooking = $this->bookingRepository->findOrFail($id);
        $updatedBooking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);

        return response()->json([
            'success' => true,
            'message' => 'Patient checked in successfully.',
            'data' => $updatedBooking,
        ]);
    }

    /**
     * Complete a booking (vaccination done).
     *
     * PATCH /api/v1/bookings/{id}/complete
     */
    public function complete(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->findOrFail($id);

        if ($booking->status !== 'checked_in') {
            return response()->json([
                'success' => false,
                'message' => 'Completion failed.',
                'errors' => ['status' => ["Cannot complete a booking with status '{$booking->status}'. Must be 'checked_in'."]],
            ], 422);
        }

        $this->bookingRepository->updateStatus($id, 'completed', [
            'completed_at' => Carbon::now(),
        ]);

        $updatedBooking = $this->bookingRepository->findOrFail($id);
        $updatedBooking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);

        return response()->json([
            'success' => true,
            'message' => 'Booking completed successfully.',
            'data' => $updatedBooking,
        ]);
    }

    /**
     * Cancel a booking.
     *
     * PATCH /api/v1/bookings/{id}/cancel
     */
    public function cancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        $booking = $this->bookingRepository->findOrFail($id);

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation failed.',
                'errors' => ['status' => ["Cannot cancel a booking that is already {$booking->status}."]],
            ], 422);
        }

        $reason = $request->validated('cancellation_reason');

        $this->bookingRepository->updateStatus($id, 'cancelled', [
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => $reason,
        ]);

        $updatedBooking = $this->bookingRepository->findOrFail($id);
        $updatedBooking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => $updatedBooking,
        ]);
    }
}
