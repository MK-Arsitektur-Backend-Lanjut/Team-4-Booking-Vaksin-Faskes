<?php

namespace App\Repositories\Contracts;

use App\Repositories\Base\BaseRepositoryInterface;

interface VaccineRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active vaccines
     */
    public function getActive();

    /**
     * Search vaccines by name or code
     */
    public function search(string $query);
}
