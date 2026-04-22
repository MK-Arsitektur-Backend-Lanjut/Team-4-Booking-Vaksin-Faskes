<?php

namespace Database\Seeders;

use App\Models\HealthCenter;
use Illuminate\Database\Seeder;

class HealthCenterSeeder extends Seeder
{
    /**
     * Seed 10,000 health centers.
     */
    public function run(): void
    {
        // Create in chunks to avoid memory issues
        for ($i = 0; $i < 20; $i++) {
            HealthCenter::factory(500)->create();
        }
    }
}
