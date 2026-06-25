<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LargeScaleSimulationSeeder::class);
        // Default user (create if not exists)
        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => bcrypt('password'),
        ]);

        // Queue & Appointment module seeders.
        $this->call([
            VaccineSeeder::class,
            HealthCenterSeeder::class,
            ScheduleSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
