<?php

namespace App\Repositories\Patient\Eloquent;

use App\Models\Patient;
use App\Repositories\Base\EloquentBaseRepository;
use App\Repositories\Patient\Contracts\PatientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientRepository extends EloquentBaseRepository implements PatientRepositoryInterface
{
    public function __construct(Patient $model)
    {
        parent::__construct($model);
    }

    public function findByNik(string $nik): ?Patient
    {
        return $this->query()->where('nik', $nik)->first();
    }

    public function findByExternalIdOrFail(string $externalPatientId): Patient
    {
        return $this->query()->where('patient_id', $externalPatientId)->firstOrFail();
    }

    public function searchPaginated(?string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->orderByDesc('id');

        if (! empty($keyword)) {
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('patient_id', 'like', "%{$keyword}%")
                    ->orWhere('nik', 'like', "%{$keyword}%")
                    ->orWhere('full_name', 'like', "%{$keyword}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function markIdentityAsVerified(Patient $patient): Patient
    {
        $patient->forceFill([
            'identity_verification_status' => 'verified',
            'identity_verified_at' => now(),
        ])->save();

        return $patient->refresh();
    }
}
