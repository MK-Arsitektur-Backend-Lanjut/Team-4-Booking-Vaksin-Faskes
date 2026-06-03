<?php

namespace App\Services;

use App\Models\Patient;
use App\Repositories\Patient\Contracts\HealthHistoryRepositoryInterface;
use App\Repositories\Patient\Contracts\PatientRepositoryInterface;
use App\Repositories\Patient\Contracts\VaccinationHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientRegistrationService
{
    public function __construct(
        private readonly PatientRepositoryInterface $patientRepository,
        private readonly HealthHistoryRepositoryInterface $healthHistoryRepository,
        private readonly VaccinationHistoryRepositoryInterface $vaccinationHistoryRepository,
    ) {
    }

    public function register(array $payload): Patient
    {
        return DB::transaction(function () use ($payload): Patient {
            return $this->patientRepository->create([
                'patient_id' => 'PAT-'.Str::upper((string) Str::ulid()),
                'nik' => $payload['nik'],
                'full_name' => $payload['full_name'],
                'birth_date' => $payload['birth_date'],
                'gender' => $payload['gender'],
                'phone_number' => $payload['phone_number'] ?? null,
                'address' => $payload['address'] ?? null,
                'identity_verification_status' => 'pending',
                'identity_verified_at' => null,
            ]);
        });
    }

    public function search(?string $keyword, int $perPage = 15): LengthAwarePaginator
    {
        $boundedPerPage = min(max($perPage, 1), 100);

        return $this->patientRepository->searchPaginated($keyword, $boundedPerPage);
    }

    public function verifyNikAndIdentity(string $nik, string $birthDate): Patient
    {
        $cacheKey = "patient_identity_verify_{$nik}_{$birthDate}";

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($nik, $birthDate) {
            $patient = $this->patientRepository->findByNik($nik);
            $patientBirthDate = $patient?->birth_date ? Carbon::parse($patient->birth_date)->toDateString() : null;

            if (! $patient || $patientBirthDate !== $birthDate) {
                throw ValidationException::withMessages([
                    'nik' => ['NIK atau data identitas tidak valid.'],
                ]);
            }

            if ($patient->identity_verification_status !== 'verified') {
                return $this->patientRepository->markIdentityAsVerified($patient);
            }

            return $patient;
        });
    }

    public function getHealthHistories(int $patientId): Collection
    {
        return $this->healthHistoryRepository->getByPatient($patientId);
    }

    public function addHealthHistory(int $patientId, array $payload)
    {
        return $this->healthHistoryRepository->createForPatient($patientId, $payload);
    }

    public function updateHealthHistory(int $patientId, string $externalHistoryId, array $payload)
    {
        return $this->healthHistoryRepository->updateForPatient($patientId, $externalHistoryId, $payload);
    }

    public function deleteHealthHistory(int $patientId, string $externalHistoryId): bool
    {
        return $this->healthHistoryRepository->deleteForPatient($patientId, $externalHistoryId);
    }

    public function getVaccinationHistories(int $patientId): Collection
    {
        return $this->vaccinationHistoryRepository->getByPatient($patientId);
    }

    public function addVaccinationHistory(int $patientId, array $payload)
    {
        return $this->vaccinationHistoryRepository->createForPatient($patientId, $payload);
    }

    public function updateVaccinationHistory(int $patientId, string $externalHistoryId, array $payload)
    {
        return $this->vaccinationHistoryRepository->updateForPatient($patientId, $externalHistoryId, $payload);
    }

    public function deleteVaccinationHistory(int $patientId, string $externalHistoryId): bool
    {
        return $this->vaccinationHistoryRepository->deleteForPatient($patientId, $externalHistoryId);
    }
}
