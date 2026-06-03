<?php

namespace Database\Factories;

use App\Models\Faskes;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
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

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-30 days', '+60 days');
        $capacity = $this->faker->numberBetween(50, 500);

        return [
            'faskes_id' => Faskes::factory(),
            'service_type' => 'vaccination',
            'vaccine_name' => $this->faker->randomElement(['Sinovac', 'AstraZeneca', 'Pfizer', 'Moderna']),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+2 hours'),
            'capacity' => $capacity,
            'booked_count' => $this->faker->numberBetween(0, $capacity),
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
