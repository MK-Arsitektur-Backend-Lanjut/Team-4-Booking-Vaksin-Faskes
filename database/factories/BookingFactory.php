<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Patient;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'confirmed', 'checked_in', 'completed', 'cancelled']);
        $bookedAt = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'schedule_id' => Schedule::factory(),
            'patient_id' => Patient::factory(),
            'queue_number' => 1, // Will be properly set in seeder
            'status' => $status,
            'booked_at' => $bookedAt,
            'checked_in_at' => in_array($status, ['checked_in', 'completed']) ? fake()->dateTimeBetween($bookedAt, 'now') : null,
            'completed_at' => $status === 'completed' ? fake()->dateTimeBetween($bookedAt, 'now') : null,
            'cancelled_at' => $status === 'cancelled' ? fake()->dateTimeBetween($bookedAt, 'now') : null,
            'cancellation_reason' => $status === 'cancelled' ? fake()->sentence() : null,
        ];
    }
}
