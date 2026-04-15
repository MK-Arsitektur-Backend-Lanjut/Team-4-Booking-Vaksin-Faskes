<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Repositories\Base\EloquentBaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentBookingRepository extends EloquentBaseRepository implements BookingRepositoryInterface
{
    public function __construct(Booking $model)
    {
        parent::__construct($model);
    }

    /**
     * Find bookings by schedule, optionally filtered by status.
     */
    public function findBySchedule(int $scheduleId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('schedule_id', $scheduleId)
            ->with(['patient', 'schedule.healthCenter', 'schedule.vaccine']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('queue_number')->paginate($perPage);
    }

    /**
     * Find all bookings for a specific patient.
     */
    public function findByPatient(int $patientId): Collection
    {
        return $this->query()
            ->where('patient_id', $patientId)
            ->with(['schedule.healthCenter', 'schedule.vaccine'])
            ->orderByDesc('booked_at')
            ->get();
    }

    /**
     * Get the next queue number for a given schedule.
     */
    public function getNextQueueNumber(int $scheduleId): int
    {
        $maxQueue = $this->query()
            ->where('schedule_id', $scheduleId)
            ->max('queue_number');

        return ($maxQueue ?? 0) + 1;
    }

    /**
     * Get quota usage for a schedule (total, used, available).
     */
    public function getQuotaUsage(int $scheduleId): array
    {
        $schedule = \App\Models\Schedule::findOrFail($scheduleId);

        $used = $this->query()
            ->where('schedule_id', $scheduleId)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        return [
            'schedule_id' => $scheduleId,
            'total' => $schedule->quota,
            'used' => $used,
            'available' => max(0, $schedule->quota - $used),
        ];
    }

    /**
     * Update the status of a booking.
     */
    public function updateStatus(int $bookingId, string $status, array $extra = []): bool
    {
        $data = array_merge(['status' => $status], $extra);

        return $this->update($bookingId, $data);
    }

    /**
     * Check if a patient already has a booking for a given schedule.
     */
    public function checkDuplicateBooking(int $scheduleId, int $patientId): bool
    {
        return $this->query()
            ->where('schedule_id', $scheduleId)
            ->where('patient_id', $patientId)
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }
}
