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
        return $this->query()
            ->select(['id', 'patient_id', 'nik', 'full_name', 'birth_date', 'gender', 'identity_verification_status', 'identity_verified_at'])
            ->where('nik', $nik)
            ->first();
    }

    public function findByExternalIdOrFail(string $externalPatientId): Patient
    {
        return $this->query()
            ->select(['id', 'patient_id', 'nik', 'full_name', 'birth_date', 'gender', 'phone_number', 'address', 'identity_verification_status', 'identity_verified_at'])
            ->where('patient_id', $externalPatientId)
            ->firstOrFail();
    }

    public function searchPaginated(?string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        // Select only necessary columns to reduce IO
        $query = $this->query()
            ->select(['id', 'patient_id', 'nik', 'full_name', 'phone_number', 'identity_verification_status', 'identity_verified_at'])
            ->orderByDesc('id');

        if (! empty($keyword)) {
            $query->where(function ($builder) use ($keyword) {
                // Route to a single index based on keyword pattern instead of OR across 3 indexes.
                // OR clauses force MySQL to merge multiple index scans — much slower on large datasets.
                if (str_starts_with($keyword, 'PAT-')) {
                    // Exact match on patient_id — uses UNIQUE index
                    $builder->where('patient_id', $keyword);
                } elseif (ctype_digit($keyword)) {
                    // Numeric input → NIK prefix search — uses UNIQUE(nik) index
                    $builder->where('nik', 'like', "{$keyword}%");
                } else {
                    // Default: name prefix search — uses full_name index
                    $builder->where('full_name', 'like', "{$keyword}%");
                }
            });
        }

        return $query->paginate($perPage);
    }

    public function markIdentityAsVerified(Patient $patient): Patient
    {
        $patient->fill([
            'identity_verification_status' => 'verified',
            'identity_verified_at' => now(),
        ])->save();

        return $patient->refresh();
    }
}
