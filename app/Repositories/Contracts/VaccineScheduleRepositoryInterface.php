<?php

namespace App\Repositories\Contracts;

use App\Repositories\Base\BaseRepositoryInterface;

interface VaccineScheduleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get schedules by health center
     */
    public function getByHealthCenter(int $healthCenterId);

    /**
     * Get schedules by date
     */
    public function getByDate(string $date);

    /**
     * Get available schedules (available_quota > 0)
     */
    public function getAvailable();

    /**
     * Get schedules by health center and date range
     */
    public function getByHealthCenterAndDateRange(int $healthCenterId, string $startDate, string $endDate);

    /**
     * Find schedule by health center, vaccine and date
     */
    public function findByHealthCenterVaccineDate(int $healthCenterId, int $vaccineId, string $date);

    /**
     * Update available quota
     */
    public function updateQuota(int $id, int $quantity);
}
