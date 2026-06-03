<?php

namespace App\Repositories;

use App\Models\VaccineSchedule;
use App\Repositories\Base\EloquentBaseRepository;
use App\Repositories\Contracts\VaccineScheduleRepositoryInterface;

class VaccineScheduleRepository extends EloquentBaseRepository implements VaccineScheduleRepositoryInterface
{
    public function __construct(VaccineSchedule $model)
    {
        parent::__construct($model);
    }

    public function getByHealthCenter(int $healthCenterId)
    {
        return $this->query()
            ->where('health_center_id', $healthCenterId)
            ->with(['healthCenter', 'vaccine'])
            ->orderBy('schedule_date')
            ->get();
    }

    public function getByDate(string $date)
    {
        return $this->query()
            ->where('schedule_date', $date)
            ->with(['healthCenter', 'vaccine'])
            ->get();
    }

    public function getAvailable()
    {
        return $this->query()
            ->where('available_quota', '>', 0)
            ->with(['healthCenter', 'vaccine'])
            ->orderBy('schedule_date')
            ->get();
    }

    public function getByHealthCenterAndDateRange(int $healthCenterId, string $startDate, string $endDate)
    {
        return $this->query()
            ->where('health_center_id', $healthCenterId)
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->with(['healthCenter', 'vaccine'])
            ->orderBy('schedule_date')
            ->get();
    }

    public function findByHealthCenterVaccineDate(int $healthCenterId, int $vaccineId, string $date)
    {
        return $this->query()
            ->where('health_center_id', $healthCenterId)
            ->where('vaccine_id', $vaccineId)
            ->where('schedule_date', $date)
            ->first();
    }

    public function updateQuota(int $id, int $quantity)
    {
        $schedule = $this->find($id);
        if ($schedule) {
            $newAvailable = max(0, $schedule->available_quota - $quantity);
            return $this->update($id, [
                'available_quota' => $newAvailable,
                'booked_quota' => $schedule->quota - $newAvailable,
            ]);
        }
        return false;
    }
}
