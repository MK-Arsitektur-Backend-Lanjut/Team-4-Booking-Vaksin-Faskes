<?php

namespace App\Repositories\Contracts;

use App\Repositories\Base\BaseRepositoryInterface;

interface VaccineStockRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get vaccine stocks by health center
     */
    public function getByHealthCenter(int $healthCenterId);

    /**
     * Get available vaccine stocks (with available_stock > 0)
     */
    public function getAvailable();

    /**
     * Get stocks by health center and vaccine
     */
    public function getByHealthCenterAndVaccine(int $healthCenterId, int $vaccineId);

    /**
     * Find by health center, vaccine and expiration date
     */
    public function findByHealthCenterVaccineExpiration(int $healthCenterId, int $vaccineId, string $expirationDate);

    /**
     * Update available stock
     */
    public function updateAvailableStock(int $id, int $quantity);
}
