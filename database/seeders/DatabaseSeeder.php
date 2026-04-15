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

        // Module 3: Queue & Appointment seeders
        $this->call([
            HealthCenterSeeder::class,  // 50 health centers
            VaccineSeeder::class,       // 6 vaccine types
            ScheduleSeeder::class,      // 200 schedules
            PatientSeeder::class,       // 2,000 patients
            BookingSeeder::class,       // 10,000 bookings
        ]);
    }
}
