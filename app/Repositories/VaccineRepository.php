<?php

namespace App\Repositories;

use App\Models\Vaccine;
use App\Repositories\Base\EloquentBaseRepository;
use App\Repositories\Contracts\VaccineRepositoryInterface;

class VaccineRepository extends EloquentBaseRepository implements VaccineRepositoryInterface
{
    public function __construct(Vaccine $model)
    {
        parent::__construct($model);
    }

    public function getActive()
    {
        return $this->query()
            ->where('status', 'active')
            ->get();
    }

    public function search(string $query)
    {
        return $this->query()
            ->where('name', 'LIKE', "%$query%")
            ->orWhere('code', 'LIKE', "%$query%")
            ->get();
    }
}
