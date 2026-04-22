<?php

namespace App\Repositories;

use App\Models\HealthCenter;
use App\Repositories\Base\EloquentBaseRepository;
use App\Repositories\Contracts\HealthCenterRepositoryInterface;

class HealthCenterRepository extends EloquentBaseRepository implements HealthCenterRepositoryInterface
{
    public function __construct(HealthCenter $model)
    {
        parent::__construct($model);
    }

    public function getByProvince(string $province)
    {
        return $this->query()
            ->where('province', $province)
            ->get();
    }

    public function getByCity(string $city)
    {
        return $this->query()
            ->where('city', $city)
            ->get();
    }

    public function search(string $query)
    {
        return $this->query()
            ->where('name', 'LIKE', "%$query%")
            ->orWhere('code', 'LIKE', "%$query%")
            ->orWhere('city', 'LIKE', "%$query%")
            ->get();
    }

    public function getActive()
    {
        return $this->query()
            ->where('status', 'active')
            ->get();
    }
}
