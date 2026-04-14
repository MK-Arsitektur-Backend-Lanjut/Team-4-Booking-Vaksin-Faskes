<?php

namespace App\Repositories\Patient\Contracts;

use App\Models\HealthHistory;
use App\Repositories\Base\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface HealthHistoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPatient(int $patientId): Collection;

    public function createForPatient(int $patientId, array $data): HealthHistory;

    public function updateForPatient(int $patientId, string $externalHistoryId, array $data): ?HealthHistory;

    public function deleteForPatient(int $patientId, string $externalHistoryId): bool;
}
