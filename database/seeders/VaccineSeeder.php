<?php

namespace Database\Seeders;

use App\Models\Vaccine;
use Illuminate\Database\Seeder;

class VaccineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vaccines = [
            [
                'name' => 'Pfizer-BioNTech COVID-19',
                'code' => 'VAC-PFIZER-001',
                'description' => 'mRNA vaccine developed by Pfizer and BioNTech',
                'doses_required' => 2,
                'days_between_doses' => 21,
                'manufacturer' => 'Pfizer/BioNTech',
                'status' => 'active',
            ],
            [
                'name' => 'Moderna COVID-19',
                'code' => 'VAC-MODERNA-001',
                'description' => 'mRNA vaccine developed by Moderna',
                'doses_required' => 2,
                'days_between_doses' => 28,
                'manufacturer' => 'Moderna',
                'status' => 'active',
            ],
            [
                'name' => 'AstraZeneca COVID-19',
                'code' => 'VAC-ASTRA-001',
                'description' => 'Viral vector vaccine developed by Oxford/AstraZeneca',
                'doses_required' => 2,
                'days_between_doses' => 28,
                'manufacturer' => 'Oxford/AstraZeneca',
                'status' => 'active',
            ],
            [
                'name' => 'Sinovac COVID-19',
                'code' => 'VAC-SINOVAC-001',
                'description' => 'Inactivated vaccine developed by Sinovac',
                'doses_required' => 2,
                'days_between_doses' => 28,
                'manufacturer' => 'Sinovac',
                'status' => 'active',
            ],
            [
                'name' => 'Janssen (Johnson & Johnson)',
                'code' => 'VAC-JANSSEN-001',
                'description' => 'Viral vector vaccine developed by Janssen',
                'doses_required' => 1,
                'days_between_doses' => null,
                'manufacturer' => 'Johnson & Johnson',
                'status' => 'active',
            ],
            [
                'name' => 'Sinopharm COVID-19',
                'code' => 'VAC-SINOPHARM-001',
                'description' => 'Inactivated vaccine developed by Sinopharm',
                'doses_required' => 2,
                'days_between_doses' => 21,
                'manufacturer' => 'Sinopharm',
                'status' => 'active',
            ],
            [
                'name' => 'Novavax COVID-19',
                'code' => 'VAC-NOVAVAX-001',
                'description' => 'Recombinant nanoparticle protein vaccine',
                'doses_required' => 2,
                'days_between_doses' => 21,
                'manufacturer' => 'Novavax',
                'status' => 'active',
            ],
            [
                'name' => 'Measles, Mumps, Rubella (MMR)',
                'code' => 'VAC-MMR-001',
                'description' => 'Live attenuated vaccine for MMR',
                'doses_required' => 2,
                'days_between_doses' => 28,
                'manufacturer' => 'Merck',
                'status' => 'active',
            ],
            [
                'name' => 'Tetanus Protection',
                'code' => 'VAC-TETANUS-001',
                'description' => 'Tetanus toxoid vaccine',
                'doses_required' => 3,
                'days_between_doses' => 30,
                'manufacturer' => 'Various',
                'status' => 'active',
            ],
            [
                'name' => 'Influenza (Flu Shot)',
                'code' => 'VAC-FLU-001',
                'description' => 'Annual influenza vaccine',
                'doses_required' => 1,
                'days_between_doses' => null,
                'manufacturer' => 'GSK',
                'status' => 'active',
            ],
            [
                'name' => 'Hepatitis B',
                'code' => 'VAC-HEPB-001',
                'description' => 'Hepatitis B vaccine',
                'doses_required' => 3,
                'days_between_doses' => 30,
                'manufacturer' => 'Merck',
                'status' => 'active',
            ],
            [
                'name' => 'Polio Vaccine',
                'code' => 'VAC-POLIO-001',
                'description' => 'Inactivated Polio Vaccine (IPV)',
                'doses_required' => 4,
                'days_between_doses' => 30,
                'manufacturer' => 'Sanofi',
                'status' => 'active',
            ],
            [
                'name' => 'HPV Vaccine',
                'code' => 'VAC-HPV-001',
                'description' => 'Human Papillomavirus vaccine',
                'doses_required' => 2,
                'days_between_doses' => 180,
                'manufacturer' => 'Merck/GSK',
                'status' => 'active',
            ],
        ];

        foreach ($vaccines as $vaccine) {
            Vaccine::create($vaccine);
        }
    }
}
