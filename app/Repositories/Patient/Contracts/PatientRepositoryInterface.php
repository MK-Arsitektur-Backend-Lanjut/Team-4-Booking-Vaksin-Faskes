<?php

namespace App\Repositories\Patient\Contracts;

use App\Models\Patient;
use App\Repositories\Base\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PatientRepositoryInterface extends BaseRepositoryInterface
{
    public function findByNik(string $nik): ?Patient;

    public function findByExternalIdOrFail(string $externalPatientId): Patient;

    public function searchPaginated(?string $keyword, int $perPage = 15): LengthAwarePaginator;

    public function markIdentityAsVerified(Patient $patient): Patient;
}
