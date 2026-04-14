<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register(): void
    {
        $response = $this->postJson('/api/v1/patients', [
            'nik' => '3201010101010001',
            'full_name' => 'Budi Santoso',
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'phone_number' => '081234567890',
            'address' => 'Bandung',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nik', '3201010101010001')
            ->assertJsonPath('data.identity_verification_status', 'pending');

        $this->assertDatabaseHas('patients', [
            'nik' => '3201010101010001',
            'full_name' => 'Budi Santoso',
        ]);
    }

    public function test_identity_can_be_verified_using_nik_and_birth_date(): void
    {
        Patient::factory()->create([
            'nik' => '3201010101010002',
            'birth_date' => '1990-05-12',
            'identity_verification_status' => 'pending',
            'identity_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/patients/verify-identity', [
            'nik' => '3201010101010002',
            'birth_date' => '1990-05-12',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.identity_verification_status', 'verified');

        $this->assertDatabaseHas('patients', [
            'nik' => '3201010101010002',
            'identity_verification_status' => 'verified',
        ]);
    }

    public function test_health_and_vaccination_histories_can_be_managed(): void
    {
        $patient = Patient::factory()->create();
        $patientExternalId = $patient->patient_id;

        $healthResponse = $this->postJson("/api/v1/patients/{$patientExternalId}/health-histories", [
            'condition_name' => 'Hipertensi',
            'diagnosed_at' => '2020-01-01',
            'notes' => 'Kontrol rutin',
        ]);

        $healthResponse
            ->assertCreated()
            ->assertJsonPath('data.condition_name', 'Hipertensi');

        $vaccineResponse = $this->postJson("/api/v1/patients/{$patientExternalId}/vaccination-histories", [
            'vaccine_name' => 'Pfizer',
            'dose_number' => 2,
            'vaccinated_at' => '2022-08-12 09:00:00',
            'provider_name' => 'Faskes Bandung',
            'notes' => 'Tidak ada KIPI',
        ]);

        $vaccineResponse
            ->assertCreated()
            ->assertJsonPath('data.vaccine_name', 'Pfizer');

        $this->getJson("/api/v1/patients/{$patientExternalId}/health-histories")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/patients/{$patientExternalId}/vaccination-histories")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $healthId = $healthResponse->json('data.health_history_id');
        $vaccinationId = $vaccineResponse->json('data.vaccination_history_id');

        $this->putJson("/api/v1/patients/{$patientExternalId}/health-histories/{$healthId}", [
            'condition_name' => 'Diabetes',
            'notes' => 'Perlu kontrol gula darah',
        ])
            ->assertOk()
            ->assertJsonPath('data.condition_name', 'Diabetes');

        $this->putJson("/api/v1/patients/{$patientExternalId}/vaccination-histories/{$vaccinationId}", [
            'dose_number' => 3,
            'notes' => 'Booster kedua',
        ])
            ->assertOk()
            ->assertJsonPath('data.dose_number', 3);

        $this->deleteJson("/api/v1/patients/{$patientExternalId}/health-histories/{$healthId}")
            ->assertOk();

        $this->deleteJson("/api/v1/patients/{$patientExternalId}/vaccination-histories/{$vaccinationId}")
            ->assertOk();

        $this->assertDatabaseMissing('health_histories', ['id' => $healthId]);
        $this->assertDatabaseMissing('vaccination_histories', ['id' => $vaccinationId]);
    }
}
