<?php

namespace App\Repositories;

use App\Repositories\Base\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BookingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find bookings by schedule, optionally filtered by status.
     */
    public function findBySchedule(int $scheduleId, ?string $status = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find all bookings for a specific patient.
     */
    public function findByPatient(int $patientId): Collection;

    /**
     * Get the next queue number for a given schedule.
     */
    public function getNextQueueNumber(int $scheduleId): int;

    /**
     * Get quota usage for a schedule (total, used, available).
     */
    public function getQuotaUsage(int $scheduleId): array;

    /**
     * Update the status of a booking.
     */
    public function updateStatus(int $bookingId, string $status, array $extra = []): bool;

    /**
     * Check if a patient already has a booking for a given schedule.
     */
    public function checkDuplicateBooking(int $scheduleId, int $patientId): bool;
}
