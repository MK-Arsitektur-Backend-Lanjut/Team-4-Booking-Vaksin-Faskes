<?php

namespace Database\Factories;

use App\Models\Faskes;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
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
        ];
    }
}
