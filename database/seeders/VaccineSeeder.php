<?php

namespace Database\Seeders;

use App\Models\Vaccine;
use Illuminate\Database\Seeder;

class VaccineSeeder extends Seeder
{
    /**
     * Seed vaccine types.
     */
    public function run(): void
    {
        $vaccines = [
            ['name' => 'Sinovac'],
            ['name' => 'AstraZeneca'],
            ['name' => 'Pfizer-BioNTech'],
            ['name' => 'Moderna'],
            ['name' => 'Johnson & Johnson'],
            ['name' => 'Sinopharm'],
        ];

        foreach ($vaccines as $vaccine) {
            Vaccine::create($vaccine);
        }
    }
}
