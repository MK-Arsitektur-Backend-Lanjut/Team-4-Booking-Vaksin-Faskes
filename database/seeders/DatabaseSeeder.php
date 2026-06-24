<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Queue & Appointment module seeders.
        $this->call([
            VaccineSeeder::class,
            HealthCenterSeeder::class,
            ScheduleSeeder::class,
            PatientSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
