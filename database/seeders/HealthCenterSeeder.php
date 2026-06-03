<?php

namespace Database\Seeders;

use App\Models\HealthCenter;
use Illuminate\Database\Seeder;

class HealthCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinces = [
            'JAKARTA', 'JAWA BARAT', 'JAWA TENGAH', 'JAWA TIMUR', 'SUMATERA UTARA',
            'SUMATERA BARAT', 'SUMATERA SELATAN', 'LAMPUNG', 'RIAU',
            'SULAWESI UTARA', 'SULAWESI TENGAH', 'SULAWESI SELATAN', 'SULAWESI TENGGARA',
            'KALIMANTAN BARAT', 'KALIMANTAN TENGAH', 'KALIMANTAN SELATAN', 'KALIMANTAN TIMUR',
            'BALI', 'NUSA TENGGARA BARAT', 'NUSA TENGGARA TIMUR',
            'PAPUA', 'PAPUA BARAT', 'MALUKU', 'MALUKU UTARA',
        ];

        $cities_by_province = [
            'JAKARTA' => ['JAKARTA PUSAT', 'JAKARTA BARAT', 'JAKARTA SELATAN', 'JAKARTA TIMUR', 'JAKARTA UTARA'],
            'JAWA BARAT' => ['BANDUNG', 'BOGOR', 'DEPOK', 'BEKASI', 'TANGERANG', 'CIANJUR', 'GARUT', 'TASIKMALAYA'],
            'JAWA TENGAH' => ['SEMARANG', 'SOLO', 'SUKOHARJO', 'PURWOKERTO', 'KENDAL', 'PEKALONGAN', 'YOGYAKARTA'],
            'JAWA TIMUR' => ['SURABAYA', 'SIDOARJO', 'GRESIK', 'MALANG', 'JEMBER', 'LAMONGAN', 'SAMPANG'],
            'SUMATERA UTARA' => ['MEDAN', 'BINJAI', 'DELI SERDANG', 'LANGKAT', 'ASAHAN'],
            'SUMATERA BARAT' => ['PADANG', 'BUKITTINGGI', 'PAYAKUMBUH', 'PARIAMAN'],
            'SUMATERA SELATAN' => ['PALEMBANG', 'MUSI RAWAS', 'OGAN KOMERING'],
            'LAMPUNG' => ['BANDAR LAMPUNG', 'METRO', 'LAMPUNG UTARA'],
            'RIAU' => ['PEKANBARU', 'DUMAI', 'ROKAN HILIR'],
            'SULAWESI UTARA' => ['MANADO', 'BITUNG', 'TOMOHON'],
            'SULAWESI TENGAH' => ['PALU', 'MOROWALI'],
            'SULAWESI SELATAN' => ['MAKASSAR', 'PAREPARE', 'PALOPO'],
            'SULAWESI TENGGARA' => ['KENDARI', 'KOLAKA'],
            'KALIMANTAN BARAT' => ['PONTIANAK', 'SINGKAWANG', 'MEMPAWAH'],
            'KALIMANTAN TENGAH' => ['PALANGKA RAYA', 'SAMPIT'],
            'KALIMANTAN SELATAN' => ['BANJARMASIN', 'BANJARBARU'],
            'KALIMANTAN TIMUR' => ['SAMARINDA', 'BALIKPAPAN', 'TARAKAN'],
            'BALI' => ['DENPASAR', 'BADUNG', 'GIANYAR'],
            'NUSA TENGGARA BARAT' => ['MATARAM', 'LOMBOK UTARA'],
            'NUSA TENGGARA TIMUR' => ['KUPANG', 'FLORES TIMUR'],
            'PAPUA' => ['JAYAPURA', 'MERAUKE'],
            'PAPUA BARAT' => ['SORONG', 'MANOKWARI'],
            'MALUKU' => ['AMBON', 'BANDA'],
            'MALUKU UTARA' => ['TERNATE', 'TIDORE'],
        ];

        $healthCenterCount = 0;

        foreach ($provinces as $province) {
            $cities = $cities_by_province[$province] ?? [$province];

            foreach ($cities as $city) {
                // Create 5 health centers per city (minimum 5000+ health centers total)
                for ($i = 1; $i <= 5; $i++) {
                    HealthCenter::create([
                        'name' => "Puskesmas {$city} {$i}",
                        'code' => strtoupper(substr($city, 0, 3)) . '-' . str_pad($healthCenterCount++, 5, '0', STR_PAD_LEFT),
                        'address' => "Jalan Vaksinasi No. {$i}, " . $city,
                        'province' => $province,
                        'city' => $city,
                        'district' => 'Kecamatan ' . $city,
                        'village' => 'Kelurahan Vaksin ' . $i,
                        'latitude' => mt_rand(-9000000, 6000000) / 1000000,
                        'longitude' => mt_rand(95000000, 141000000) / 1000000,
                        'phone' => '+62-' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT),
                        'capacity' => mt_rand(50, 200),
                        'status' => rand(0, 1) ? 'active' : 'inactive',
                    ]);
                }
            }
        }
    }
}
