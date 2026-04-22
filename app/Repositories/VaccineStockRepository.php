<?php

namespace App\Repositories;

use App\Models\VaccineStock;
use App\Repositories\Base\EloquentBaseRepository;
use App\Repositories\Contracts\VaccineStockRepositoryInterface;

class VaccineStockRepository extends EloquentBaseRepository implements VaccineStockRepositoryInterface
{
    public function __construct(VaccineStock $model)
    {
        parent::__construct($model);
    }

    public function getByHealthCenter(int $healthCenterId)
    {
        return $this->query()
            ->where('health_center_id', $healthCenterId)
            ->with(['healthCenter', 'vaccine'])
            ->get();
    }

    public function getAvailable()
    {
        return $this->query()
            ->where('available_stock', '>', 0)
            ->with(['healthCenter', 'vaccine'])
            ->get();
    }

    public function getByHealthCenterAndVaccine(int $healthCenterId, int $vaccineId)
    {
        return $this->query()
            ->where('health_center_id', $healthCenterId)
            ->where('vaccine_id', $vaccineId)
            ->with(['healthCenter', 'vaccine'])
            ->get();
    }

    public function findByHealthCenterVaccineExpiration(int $healthCenterId, int $vaccineId, string $expirationDate)
    {
        return $this->query()
            ->where('health_center_id', $healthCenterId)
            ->where('vaccine_id', $vaccineId)
            ->where('expiration_date', $expirationDate)
            ->first();
    }

    public function updateAvailableStock(int $id, int $quantity)
    {
        $stock = $this->find($id);
        if ($stock) {
            $newAvailable = max(0, $stock->available_stock - $quantity);
            return $this->update($id, [
                'available_stock' => $newAvailable,
                'used_stock' => $stock->total_stock - $newAvailable,
            ]);
        }
        return false;
    }
}
