<?php

namespace Database\Factories;

use App\Models\HealthCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthCenter>
 */
class HealthCenterFactory extends Factory
{
    protected $model = HealthCenter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Puskesmas', 'Klinik', 'Rumah Sakit', 'Posyandu'];
        $areas = ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang', 'Medan', 'Makassar', 'Denpasar', 'Palembang', 'Malang'];

        return [
            'name' => fake()->randomElement($types) . ' ' . fake()->lastName() . ' ' . fake()->randomElement($areas),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
