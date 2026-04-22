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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Module 1 Seeders: Health Center Management & Vaccine Scheduling
        $this->call([
            VaccineSeeder::class,
            HealthCenterSeeder::class,
            VaccineStockSeeder::class,
            // VaccineScheduleSeeder is skipped for performance - has 65+ million statements
            // You can run it separately: php artisan db:seed --class=VaccineScheduleSeeder
        ]);
    }
}
