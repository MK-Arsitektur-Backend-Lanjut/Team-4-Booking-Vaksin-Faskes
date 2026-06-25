<?php

namespace Database\Seeders;

use App\Models\Vaccine;
use Illuminate\Database\Seeder;

class VaccineSeeder extends Seeder
{
    /**
     * Seed the vaccines referenced by schedules. The Queue & Appointment module
     * only needs id + name, matching the `vaccines` table on this branch.
     */
    public function run(): void
    {
        $names = [
            'Pfizer-BioNTech COVID-19',
            'Moderna COVID-19',
            'AstraZeneca COVID-19',
            'Sinovac COVID-19',
            'Janssen (Johnson & Johnson)',
            'Sinopharm COVID-19',
            'Novavax COVID-19',
            'Measles, Mumps, Rubella (MMR)',
            'Tetanus Protection',
            'Influenza (Flu Shot)',
            'Hepatitis B',
            'Polio Vaccine',
            'HPV Vaccine',
        ];

        foreach ($names as $name) {
            Vaccine::create(['name' => $name]);
        }
    }
}
