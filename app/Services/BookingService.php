<?php

namespace App\Services;

use App\Models\Booking;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
    ) {}

    /**
     * Create a new booking with automatic queue number assignment.
     *
     * @throws ValidationException
     */
    public function createBooking(int $scheduleId, int $patientId): Booking
    {
        // Check for duplicate booking
        if ($this->bookingRepository->checkDuplicateBooking($scheduleId, $patientId)) {
            throw ValidationException::withMessages([
                'patient_id' => ['Patient already has an active booking for this schedule.'],
            ]);
        }

        // Check quota availability
        $quota = $this->bookingRepository->getQuotaUsage($scheduleId);
        if ($quota['available'] <= 0) {
            throw ValidationException::withMessages([
                'schedule_id' => ['No available quota for this schedule. All slots are fully booked.'],
            ]);
        }

        // Get the next queue number
        $queueNumber = $this->bookingRepository->getNextQueueNumber($scheduleId);

        // Create the booking
        /** @var Booking $booking */
        $booking = $this->bookingRepository->create([
            'schedule_id' => $scheduleId,
            'patient_id' => $patientId,
            'queue_number' => $queueNumber,
            'status' => 'pending',
            'booked_at' => Carbon::now(),
        ]);

        return $booking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(int $bookingId, ?string $reason = null): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            throw ValidationException::withMessages([
                'status' => ["Cannot cancel a booking that is already {$booking->status}."],
            ]);
        }

        $this->bookingRepository->updateStatus($bookingId, 'cancelled', [
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => $reason,
        ]);

        return $this->bookingRepository->findOrFail($bookingId)
            ->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);
    }

    /**
     * Check in a patient (mark attendance).
     */
    public function checkIn(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        if ($booking->status !== 'pending' && $booking->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'status' => ["Cannot check in a booking with status '{$booking->status}'. Must be 'pending' or 'confirmed'."],
            ]);
        }

        $this->bookingRepository->updateStatus($bookingId, 'checked_in', [
            'checked_in_at' => Carbon::now(),
        ]);

        return $this->bookingRepository->findOrFail($bookingId)
            ->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);
    }

    /**
     * Complete a booking (vaccination done).
     */
    public function completeBooking(int $bookingId): Booking
    {
        $booking = $this->bookingRepository->findOrFail($bookingId);

        if ($booking->status !== 'checked_in') {
            throw ValidationException::withMessages([
                'status' => ["Cannot complete a booking with status '{$booking->status}'. Must be 'checked_in'."],
            ]);
        }

        $this->bookingRepository->updateStatus($bookingId, 'completed', [
            'completed_at' => Carbon::now(),
        ]);

        return $this->bookingRepository->findOrFail($bookingId)
            ->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);
    }

    /**
     * Get bookings filtered by schedule and/or status.
     */
    public function getBookingsBySchedule(int $scheduleId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->bookingRepository->findBySchedule($scheduleId, $status, $perPage);
    }

    /**
     * Get all bookings for a patient.
     */
    public function getBookingsByPatient(int $patientId): Collection
    {
        return $this->bookingRepository->findByPatient($patientId);
    }

    /**
     * Get real-time quota report for a schedule.
     */
    public function getQuotaReport(int $scheduleId): array
    {
        return $this->bookingRepository->getQuotaUsage($scheduleId);
    }

    /**
     * Get a single booking by ID with relationships loaded.
     */
    public function getBookingDetail(int $bookingId): Booking
    {
        /** @var Booking $booking */
        $booking = $this->bookingRepository->findOrFail($bookingId);

        return $booking->load(['schedule.healthCenter', 'schedule.vaccine', 'patient']);
    }
}
