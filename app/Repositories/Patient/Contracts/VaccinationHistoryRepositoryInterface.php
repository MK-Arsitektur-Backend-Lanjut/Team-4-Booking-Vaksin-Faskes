<?php

namespace App\Repositories\Patient\Contracts;

use App\Models\VaccinationHistory;
use App\Repositories\Base\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface VaccinationHistoryRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPatient(int $patientId): Collection;

    public function createForPatient(int $patientId, array $data): VaccinationHistory;

    public function updateForPatient(int $patientId, string $externalHistoryId, array $data): ?VaccinationHistory;

    public function deleteForPatient(int $patientId, string $externalHistoryId): bool;
}
