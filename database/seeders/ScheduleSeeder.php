<?php

namespace Database\Seeders;

use App\Models\Faskes;
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
        $faskesIds = Faskes::pluck('id')->toArray();
        $vaccineNames = Vaccine::pluck('name')->toArray();

        for ($i = 0; $i < 20; $i++) {
            Schedule::factory(500)->create([
                'faskes_id' => fn () => fake()->randomElement($faskesIds),
                'vaccine_name' => fn () => fake()->randomElement($vaccineNames),
            ]);
        }
    }
}
