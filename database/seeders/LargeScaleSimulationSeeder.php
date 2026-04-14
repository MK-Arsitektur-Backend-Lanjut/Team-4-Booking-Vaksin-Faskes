<?php

namespace Database\Seeders;

use App\Models\Faskes;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LargeScaleSimulationSeeder extends Seeder
{
    private const TABLE_EXTERNAL_ID_MAP = [
        'patients' => ['column' => 'patient_id', 'prefix' => 'PAT'],
        'faskes' => ['column' => 'faskes_id', 'prefix' => 'FSK'],
    ];

    public function run(): void
    {
        $patientTarget = (int) env('SEED_PATIENT_COUNT', 10000);
        $faskesTarget = (int) env('SEED_FASKES_COUNT', 10000);
        $scheduleTarget = (int) env('SEED_SCHEDULE_COUNT', 10000);
        $chunkSize = 1000;

        DB::disableQueryLog();

        if (! User::query()->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $this->seedByChunk(Patient::factory(), 'patients', $patientTarget, $chunkSize);
        $this->seedByChunk(Faskes::factory(), 'faskes', $faskesTarget, $chunkSize);

        $faskesIds = Faskes::query()->pluck('id')->all();

        $this->seedSchedulesByChunk($faskesIds, $scheduleTarget, $chunkSize);
        $this->seedPatientHistoriesByChunk($chunkSize);
    }

    private function seedByChunk(Factory $factory, string $tableName, int $targetCount, int $chunkSize): void
    {
        $currentCount = DB::table($tableName)->count();

        if ($currentCount >= $targetCount) {
            return;
        }

        $remaining = $targetCount - $currentCount;
        $runningSequence = $currentCount + 1;
        $externalId = self::TABLE_EXTERNAL_ID_MAP[$tableName] ?? null;

        while ($remaining > 0) {
            $batch = min($chunkSize, $remaining);

            $rows = $factory
                ->count($batch)
                ->make()
                ->map(function ($model) use (&$runningSequence, $externalId) {
                    $attributes = $model->getAttributes();

                    if ($externalId) {
                        $attributes[$externalId['column']] = $this->buildPublicId($externalId['prefix'], $runningSequence++);
                    }

                    $attributes['created_at'] = now();
                    $attributes['updated_at'] = now();

                    return $attributes;
                })
                ->all();

            DB::table($tableName)->insert($rows);

            $remaining -= $batch;
        }
    }

    private function seedSchedulesByChunk(array $faskesIds, int $targetCount, int $chunkSize): void
    {
        if (empty($faskesIds)) {
            return;
        }

        $vaccines = ['Sinovac', 'AstraZeneca', 'Pfizer', 'Moderna'];

        $currentCount = DB::table('schedules')->count();

        if ($currentCount >= $targetCount) {
            return;
        }

        $remaining = $targetCount - $currentCount;
        $sequence = $currentCount + 1;

        while ($remaining > 0) {
            $batch = min($chunkSize, $remaining);
            $rows = [];

            for ($index = 0; $index < $batch; $index++) {
                $start = now()->addDays(random_int(-30, 60))->setTime(random_int(7, 15), 0);
                $capacity = random_int(50, 500);

                $rows[] = [
                    'schedule_id' => $this->buildPublicId('SCH', $sequence++),
                    'faskes_id' => $faskesIds[array_rand($faskesIds)],
                    'service_type' => 'vaccination',
                    'vaccine_name' => $vaccines[array_rand($vaccines)],
                    'starts_at' => $start,
                    'ends_at' => (clone $start)->addHours(2),
                    'capacity' => $capacity,
                    'booked_count' => random_int(0, $capacity),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('schedules')->insert($rows);
            $remaining -= $batch;
        }
    }

    private function seedPatientHistoriesByChunk(int $chunkSize): void
    {
        if (DB::table('health_histories')->exists() || DB::table('vaccination_histories')->exists()) {
            return;
        }

        $healthSequence = DB::table('health_histories')->count() + 1;
        $vaccinationSequence = DB::table('vaccination_histories')->count() + 1;

        $healthConditions = [
            'Hipertensi',
            'Diabetes Tipe 2',
            'Asma',
            'Penyakit Jantung',
            'Alergi Obat',
            'Kolesterol Tinggi',
        ];

        $vaccines = ['Sinovac', 'AstraZeneca', 'Pfizer', 'Moderna'];

        Patient::query()
            ->select('id')
            ->orderBy('id')
            ->chunk($chunkSize, function ($patients) use ($healthConditions, $vaccines, &$healthSequence, &$vaccinationSequence) {
                $healthRows = [];
                $vaccinationRows = [];

                foreach ($patients as $patient) {
                    $healthCount = random_int(1, 3);
                    $vaccinationCount = random_int(1, 4);

                    for ($index = 0; $index < $healthCount; $index++) {
                        $diagnosedAt = now()->subDays(random_int(30, 3650))->toDateString();

                        $healthRows[] = [
                            'health_history_id' => $this->buildPublicId('HLT', $healthSequence++),
                            'patient_id' => $patient->id,
                            'condition_name' => $healthConditions[array_rand($healthConditions)],
                            'diagnosed_at' => $diagnosedAt,
                            'notes' => fake()->optional(0.5)->sentence(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    for ($index = 1; $index <= $vaccinationCount; $index++) {
                        $vaccinatedAt = now()->subDays(random_int(1, 1460));

                        $vaccinationRows[] = [
                            'vaccination_history_id' => $this->buildPublicId('VAC', $vaccinationSequence++),
                            'patient_id' => $patient->id,
                            'vaccine_name' => $vaccines[array_rand($vaccines)],
                            'dose_number' => min($index, 4),
                            'vaccinated_at' => $vaccinatedAt,
                            'provider_name' => 'Faskes '.fake()->city(),
                            'notes' => fake()->optional(0.5)->sentence(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (! empty($healthRows)) {
                    DB::table('health_histories')->insert($healthRows);
                }

                if (! empty($vaccinationRows)) {
                    DB::table('vaccination_histories')->insert($vaccinationRows);
                }
            });
    }

    private function buildPublicId(string $prefix, int $sequence): string
    {
        return sprintf('%s-%s-%06d', $prefix, now()->format('Ymd'), $sequence);
    }
}
