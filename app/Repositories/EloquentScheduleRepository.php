<?php

namespace App\Repositories;

use App\Models\Schedule;
use App\Repositories\Base\EloquentBaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentScheduleRepository extends EloquentBaseRepository implements ScheduleRepositoryInterface
{
    public function __construct(Schedule $model)
    {
        parent::__construct($model);
    }

    /**
     * Find schedules by health center.
     */
    public function findByHealthCenter(int $healthCenterId): Collection
    {
        return $this->query()
            ->where('health_center_id', $healthCenterId)
            ->with(['healthCenter', 'vaccine'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Find available schedules with remaining quota, filterable by date and health center.
     */
    public function findAvailable(?string $date = null, ?int $healthCenterId = null): Collection
    {
        $query = $this->query()
            ->with(['healthCenter', 'vaccine'])
            ->withCount(['bookings as booked_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }]);

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($healthCenterId) {
            $query->where('health_center_id', $healthCenterId);
        }

        return $query
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn (Schedule $schedule) => $schedule->booked_count < $schedule->quota)
            ->values();
    }

    /**
     * Find a schedule with its relationships loaded.
     */
    public function findWithRelations(int $id): ?Model
    {
        return $this->query()
            ->with(['healthCenter', 'vaccine'])
            ->withCount(['bookings as booked_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }])
            ->find($id);
    }
}
