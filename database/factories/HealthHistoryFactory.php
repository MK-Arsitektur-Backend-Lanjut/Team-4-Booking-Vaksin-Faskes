<?php

namespace Database\Factories;

use App\Models\HealthHistory;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthHistory>
 */
class HealthHistoryFactory extends Factory
{
    protected $model = HealthHistory::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'condition_name' => $this->faker->randomElement([
                'Hipertensi',
                'Diabetes',
                'Asma',
                'Alergi Obat',
                'Penyakit Jantung',
            ]),
            'diagnosed_at' => $this->faker->optional()->dateTimeBetween('-20 years', '-1 day')->format('Y-m-d'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
