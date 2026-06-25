<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            $patientIdInput = $request->input('patient_id');

            if (!is_numeric($patientIdInput)) {
                $patient = \App\Models\Patient::where('patient_id', $patientIdInput)
                    ->orWhere('nik', $patientIdInput)
                    ->first();

                $patientId = $patient ? $patient->id : null;
            } else {
                $patientId = (int) $patientIdInput;
            }

            if (! $patientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found.',
                ], 404);
            }

            $bookings = $this->bookingRepository->findByPatient($patientId);

            return response()->json([
                'success' => true,
                'message' => 'Bookings retrieved successfully.',
                'data' => $bookings,
            ]);
        }

        // Otherwise, require schedule_id. Resolve external codes (SCH-...) to numeric id if necessary.
        $scheduleInput = $request->input('schedule_id');

        if (!empty($scheduleInput) && !is_numeric($scheduleInput)) {
            $schedule = \App\Models\Schedule::where('schedule_id', $scheduleInput)->first();
            $resolvedScheduleId = $schedule ? $schedule->id : null;
            $request->merge(['schedule_id' => $resolvedScheduleId]);
        }

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

        try {
            $booking = DB::transaction(function () use ($scheduleId, $patientId) {
                // Fast, friendly rejection of an obvious duplicate before we
                // reserve a slot. The unique index below is the race-safe backstop.
                if ($this->bookingRepository->checkDuplicateBooking($scheduleId, $patientId)) {
                    throw new \RuntimeException('DUPLICATE_BOOKING');
                }

                // Atomically reserve one quota slot and allocate the next queue
                // number in a single statement. The WHERE guard makes overselling
                // impossible; the row write-lock held until commit makes the queue
                // number race-free without a wide SELECT ... FOR UPDATE.
                $reserved = DB::table('schedules')
                    ->where('id', $scheduleId)
                    ->whereColumn('booked_count', '<', 'quota')
                    ->update([
                        'booked_count' => DB::raw('booked_count + 1'),
                        'last_queue_number' => DB::raw('last_queue_number + 1'),
                    ]);

                if ($reserved === 0) {
                    throw new \RuntimeException('NO_QUOTA');
                }

                $queueNumber = (int) DB::table('schedules')
                    ->where('id', $scheduleId)
                    ->value('last_queue_number');

                return $this->bookingRepository->create([
                    'schedule_id' => $scheduleId,
                    'patient_id' => $patientId,
                    'queue_number' => $queueNumber,
                    'status' => 'pending',
                    'booked_at' => Carbon::now(),
                ]);
            }, 3); // retry deadlocks / lock-wait timeouts (and SQLite "database is locked")

            $booking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully.',
                'data' => $booking,
            ], 201);

        } catch (\RuntimeException $e) {
            return $this->bookingFailure($e->getMessage());
        } catch (QueryException $e) {
            // A concurrent request claimed the same (schedule, patient) pair first;
            // the unique index rejects this insert. Map it to the duplicate response.
            if ($e->getCode() === '23000') {
                return $this->bookingFailure('DUPLICATE_BOOKING');
            }

            throw $e;
        }
    }

    /**
     * Build the 422 response for a booking that could not be placed.
     */
    private function bookingFailure(string $reason): JsonResponse
    {
        $errors = $reason === 'NO_QUOTA'
            ? ['schedule_id' => ['No available quota for this schedule. All slots are fully booked.']]
            : ['patient_id' => ['Patient already has an active booking for this schedule.']];

        return response()->json([
            'success' => false,
            'message' => 'Booking failed.',
            'errors' => $errors,
        ], 422);
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

        DB::transaction(function () use ($id, $booking, $reason) {
            // Only the request that actually flips the status frees the slot, so
            // racing cancellations can't decrement booked_count more than once.
            $flipped = DB::table('bookings')
                ->where('id', $id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => Carbon::now(),
                    'cancellation_reason' => $reason,
                    'updated_at' => Carbon::now(),
                ]);

            if ($flipped === 1) {
                DB::table('schedules')
                    ->where('id', $booking->schedule_id)
                    ->where('booked_count', '>', 0)
                    ->decrement('booked_count');
            }
        });

        $updatedBooking = $this->bookingRepository->findOrFail($id);
        $updatedBooking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => $updatedBooking,
        ]);
    }
}
