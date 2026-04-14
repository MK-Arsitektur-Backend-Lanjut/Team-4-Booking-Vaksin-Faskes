<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHealthHistoryRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\StoreVaccinationHistoryRequest;
use App\Http\Requests\UpdateHealthHistoryRequest;
use App\Http\Requests\UpdateVaccinationHistoryRequest;
use App\Http\Requests\VerifyIdentityRequest;
use App\Repositories\Patient\Contracts\PatientRepositoryInterface;
use App\Services\PatientRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientRegistrationService $patientService,
        private readonly PatientRepositoryInterface $patientRepository,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $patients = $this->patientService->search(
            keyword: $request->query('search'),
            perPage: (int) $request->query('per_page', 15),
        );

        return response()->json($patients);
    }

    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = $this->patientService->register($request->validated());

        return response()->json([
            'message' => 'Registrasi pasien berhasil.',
            'data' => $patient,
        ], 201);
    }

    public function show(string $patientId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        return response()->json([
            'data' => $patient,
        ]);
    }

    public function verifyIdentity(VerifyIdentityRequest $request): JsonResponse
    {
        $patient = $this->patientService->verifyNikAndIdentity(
            nik: $request->string('nik')->toString(),
            birthDate: $request->string('birth_date')->toString(),
        );

        return response()->json([
            'message' => 'Identitas pasien terverifikasi.',
            'data' => $patient,
        ]);
    }

    public function healthHistories(string $patientId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        return response()->json([
            'data' => $this->patientService->getHealthHistories($patient->id),
        ]);
    }

    public function addHealthHistory(StoreHealthHistoryRequest $request, string $patientId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        $history = $this->patientService->addHealthHistory($patient->id, $request->validated());

        return response()->json([
            'message' => 'Riwayat kesehatan berhasil ditambahkan.',
            'data' => $history,
        ], 201);
    }

    public function updateHealthHistory(UpdateHealthHistoryRequest $request, string $patientId, string $historyId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        $history = $this->patientService->updateHealthHistory($patient->id, $historyId, $request->validated());

        if (! $history) {
            abort(404, 'Riwayat kesehatan tidak ditemukan.');
        }

        return response()->json([
            'message' => 'Riwayat kesehatan berhasil diperbarui.',
            'data' => $history,
        ]);
    }

    public function deleteHealthHistory(string $patientId, string $historyId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        $deleted = $this->patientService->deleteHealthHistory($patient->id, $historyId);

        if (! $deleted) {
            abort(404, 'Riwayat kesehatan tidak ditemukan.');
        }

        return response()->json([
            'message' => 'Riwayat kesehatan berhasil dihapus.',
        ]);
    }

    public function vaccinationHistories(string $patientId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        return response()->json([
            'data' => $this->patientService->getVaccinationHistories($patient->id),
        ]);
    }

    public function addVaccinationHistory(StoreVaccinationHistoryRequest $request, string $patientId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        $history = $this->patientService->addVaccinationHistory($patient->id, $request->validated());

        return response()->json([
            'message' => 'Riwayat vaksinasi berhasil ditambahkan.',
            'data' => $history,
        ], 201);
    }

    public function updateVaccinationHistory(UpdateVaccinationHistoryRequest $request, string $patientId, string $historyId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        $history = $this->patientService->updateVaccinationHistory($patient->id, $historyId, $request->validated());

        if (! $history) {
            abort(404, 'Riwayat vaksinasi tidak ditemukan.');
        }

        return response()->json([
            'message' => 'Riwayat vaksinasi berhasil diperbarui.',
            'data' => $history,
        ]);
    }

    public function deleteVaccinationHistory(string $patientId, string $historyId): JsonResponse
    {
        $patient = $this->patientRepository->findByExternalIdOrFail($patientId);

        $deleted = $this->patientService->deleteVaccinationHistory($patient->id, $historyId);

        if (! $deleted) {
            abort(404, 'Riwayat vaksinasi tidak ditemukan.');
        }

        return response()->json([
            'message' => 'Riwayat vaksinasi berhasil dihapus.',
        ]);
    }
}
