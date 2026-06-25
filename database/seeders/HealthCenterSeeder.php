<?php

namespace Database\Seeders;

use App\Models\HealthCenter;
use Illuminate\Database\Seeder;

class HealthCenterSeeder extends Seeder
{
    /**
     * Seed health centers referenced by schedules. The Queue & Appointment module
     * uses the simple `health_centers` table (name, address, phone) on this branch,
     * so only those columns are populated. The city/province names are folded into
     * the name/address to keep the seed data geographically varied.
     */
    public function run(): void
    {
        $cities = [
            'JAKARTA PUSAT', 'JAKARTA BARAT', 'JAKARTA SELATAN', 'JAKARTA TIMUR', 'JAKARTA UTARA',
            'BANDUNG', 'BOGOR', 'DEPOK', 'BEKASI', 'TANGERANG', 'CIANJUR', 'GARUT', 'TASIKMALAYA',
            'SEMARANG', 'SOLO', 'SUKOHARJO', 'PURWOKERTO', 'KENDAL', 'PEKALONGAN', 'YOGYAKARTA',
            'SURABAYA', 'SIDOARJO', 'GRESIK', 'MALANG', 'JEMBER', 'LAMONGAN', 'SAMPANG',
            'MEDAN', 'BINJAI', 'DELI SERDANG', 'PADANG', 'BUKITTINGGI', 'PALEMBANG',
            'BANDAR LAMPUNG', 'PEKANBARU', 'MANADO', 'PALU', 'MAKASSAR', 'KENDARI',
            'PONTIANAK', 'PALANGKA RAYA', 'BANJARMASIN', 'SAMARINDA', 'BALIKPAPAN',
            'DENPASAR', 'MATARAM', 'KUPANG', 'JAYAPURA', 'SORONG', 'AMBON', 'TERNATE',
        ];

        foreach ($cities as $city) {
            // 5 health centers per city.
            for ($i = 1; $i <= 5; $i++) {
                HealthCenter::create([
                    'name' => "Puskesmas {$city} {$i}",
                    'address' => "Jalan Vaksinasi No. {$i}, {$city}",
                    'phone' => '+62-' . str_pad((string) mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
                ]);
            }
        }
    }
}
