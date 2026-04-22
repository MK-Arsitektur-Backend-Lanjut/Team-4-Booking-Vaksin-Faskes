<?php

namespace Database\Seeders;

use App\Models\HealthCenter;
use App\Models\Schedule;
use App\Models\Vaccine;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Seed 10,000 schedules across all health centers and vaccines.
     */
    public function run(): void
    {
        $healthCenterIds = HealthCenter::pluck('id')->toArray();
        $vaccineIds = Vaccine::pluck('id')->toArray();

        for ($i = 0; $i < 20; $i++) {
            Schedule::factory(500)->create([
                'health_center_id' => fn () => fake()->randomElement($healthCenterIds),
                'vaccine_id' => fn () => fake()->randomElement($vaccineIds),
            ]);
        }
    }
}
