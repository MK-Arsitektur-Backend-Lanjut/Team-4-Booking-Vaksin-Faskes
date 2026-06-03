<?php

namespace Database\Factories;

use App\Models\HealthCenter;
use App\Models\Schedule;
use App\Models\Vaccine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(7, 15);
        $endHour = $startHour + fake()->numberBetween(1, 3);

        return [
            'health_center_id' => HealthCenter::factory(),
            'vaccine_id' => Vaccine::factory(),
            'date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', min($endHour, 18)),
            'quota' => fake()->randomElement([50, 75, 100, 150, 200]),
        ];
    }
}
