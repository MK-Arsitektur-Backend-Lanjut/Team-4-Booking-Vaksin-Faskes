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
            ->where('faskes_id', $healthCenterId)
            ->with(['faskes'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Find available schedules with remaining quota, filterable by date and health center.
     */
    public function findAvailable(?string $date = null, ?int $healthCenterId = null): Collection
    {
        $query = $this->query()
            ->with(['faskes'])
            ->withCount(['bookings as booked_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }]);

        if ($date) {
            // `date` is a DATE column, so a plain equality match is index-friendly.
            // whereDate() would compile to DATE(`date`) = ? and bypass the index.
            $query->where('date', $date);
        }

        if ($healthCenterId) {
            $query->where('faskes_id', $healthCenterId);
        }

        return $query
            ->havingRaw('booked_count < capacity')
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Find a schedule with its relationships loaded.
     */
    public function findWithRelations(int $id): ?Model
    {
        return $this->query()
            ->with(['faskes'])
            ->withCount(['bookings as booked_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled']);
            }])
            ->find($id);
    }
}
