<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'patient_id' => 'PAT-'.$this->faker->unique()->bothify('########-######'),
            'nik' => $this->faker->unique()->numerify('################'),
            'full_name' => $this->faker->name(),
            'birth_date' => $this->faker->dateTimeBetween('-70 years', '-1 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'phone_number' => $this->faker->numerify('08##########'),
            'address' => $this->faker->address(),
            'identity_verification_status' => $this->faker->randomElement(['pending', 'verified']),
            'identity_verified_at' => $this->faker->optional()->dateTimeBetween('-2 years', 'now'),
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nik' => fake()->unique()->numerify('################'), // 16 digits
            'name' => fake()->name(),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-5 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
        ];
    }
}
