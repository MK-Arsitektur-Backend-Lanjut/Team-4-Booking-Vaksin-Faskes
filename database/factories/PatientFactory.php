<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Patient>
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'verified']);

        return [
            // Use full ULID (uppercase) to avoid substring collisions
            'patient_id' => 'PAT-'.Str::upper((string) Str::ulid()),
            'nik' => $this->faker->unique()->numerify('################'),
            'full_name' => $this->faker->name(),
            'birth_date' => $this->faker->dateTimeBetween('-70 years', '-1 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'phone_number' => $this->faker->numerify('08##########'),
            'address' => $this->faker->address(),
            'identity_verification_status' => $status,
            'identity_verified_at' => $status === 'verified'
                ? $this->faker->dateTimeBetween('-2 years', 'now')
                : null,
        ];
    }
}
