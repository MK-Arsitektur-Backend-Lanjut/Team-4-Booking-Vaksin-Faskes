<?php

namespace Database\Seeders;

use App\Models\HealthCenter;
use Illuminate\Database\Seeder;

class HealthCenterSeeder extends Seeder
{
    /**
     * Seed 50 health centers.
     */
    public function run(): void
    {
        HealthCenter::factory(50)->create();
    }
}
