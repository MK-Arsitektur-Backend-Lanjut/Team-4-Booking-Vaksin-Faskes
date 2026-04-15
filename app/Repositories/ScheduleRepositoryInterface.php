<?php

namespace App\Repositories;

use App\Repositories\Base\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface ScheduleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find schedules by health center.
     */
    public function findByHealthCenter(int $healthCenterId): Collection;

    /**
     * Find available schedules with remaining quota, filterable by date and health center.
     */
    public function findAvailable(?string $date = null, ?int $healthCenterId = null): Collection;

    /**
     * Find a schedule with its relationships loaded.
     */
    public function findWithRelations(int $id): ?\Illuminate\Database\Eloquent\Model;
}
