<?php

namespace App\Services;

use App\Models\Schedule;
use App\Repositories\ScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ScheduleService
{
    public function __construct(
        protected ScheduleRepositoryInterface $scheduleRepository,
    ) {}

    /**
     * Get available schedules, optionally filtered by date and health center.
     */
    public function getAvailableSchedules(?string $date = null, ?int $healthCenterId = null): Collection
    {
        return $this->scheduleRepository->findAvailable($date, $healthCenterId);
    }

    /**
     * Get a single schedule with its relationships and quota info.
     */
    public function getScheduleDetail(int $scheduleId): Schedule
    {
        $schedule = $this->scheduleRepository->findWithRelations($scheduleId);

        if (! $schedule) {
            abort(404, 'Schedule not found.');
        }

        /** @var Schedule $schedule */
        return $schedule;
    }
}
