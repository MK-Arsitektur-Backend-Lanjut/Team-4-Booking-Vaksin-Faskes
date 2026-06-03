<?php

namespace Database\Factories;

use App\Models\Vaccine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vaccine>
 */
class VaccineFactory extends Factory
{
    protected $model = Vaccine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Sinovac',
                'AstraZeneca',
                'Pfizer-BioNTech',
                'Moderna',
                'Johnson & Johnson',
                'Sinopharm',
            ]),
        ];
    }
}
