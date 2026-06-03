<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\VaccinationHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaccinationHistory>
 */
class VaccinationHistoryFactory extends Factory
{
    protected $model = VaccinationHistory::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'vaccine_name' => $this->faker->randomElement(['Sinovac', 'AstraZeneca', 'Pfizer', 'Moderna']),
            'dose_number' => $this->faker->numberBetween(1, 4),
            'vaccinated_at' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'provider_name' => 'Faskes '.$this->faker->city(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
