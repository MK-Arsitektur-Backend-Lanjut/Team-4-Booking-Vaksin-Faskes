<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Seed 10,000 patients.
     */
    public function run(): void
    {
        // Create in chunks to avoid memory issues
        for ($i = 0; $i < 20; $i++) {
            Patient::factory(500)->create();
        }
    }
}
