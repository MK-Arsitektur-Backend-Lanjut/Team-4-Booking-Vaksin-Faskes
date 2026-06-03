<?php

namespace App\Repositories\Contracts;

use App\Repositories\Base\BaseRepositoryInterface;

interface HealthCenterRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get health centers by province
     */
    public function getByProvince(string $province);

    /**
     * Get health centers by city
     */
    public function getByCity(string $city);

    /**
     * Search health centers by name or code
     */
    public function search(string $query);

    /**
     * Get active health centers
     */
    public function getActive();
}
