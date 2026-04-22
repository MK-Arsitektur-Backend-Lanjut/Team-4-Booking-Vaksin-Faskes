<?php

namespace Database\Seeders;

use App\Models\VaccineStock;
use App\Models\HealthCenter;
use App\Models\Vaccine;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class VaccineStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $healthCenters = HealthCenter::pluck('id')->toArray();
        $vaccines = Vaccine::pluck('id')->toArray();

        $stockCount = 0;

        // Create vaccine stocks for each health center and vaccine combination
        // This will generate approximately 5000+ * 13 = 65000+ records
        foreach ($healthCenters as $healthCenterId) {
            foreach ($vaccines as $vaccineId) {
                // Each health center has 3-5 batches of each vaccine with different expiration dates
                $batchCount = mt_rand(3, 5);

                for ($i = 0; $i < $batchCount; $i++) {
                    $totalStock = mt_rand(50, 500);
                    $availableStock = mt_rand(10, $totalStock);
                    // Add offset days to ensure unique expiration dates
                    $expirationDate = Carbon::now()->addMonths(mt_rand(3, 24))->addDays($i * 7);

                    VaccineStock::create([
                        'health_center_id' => $healthCenterId,
                        'vaccine_id' => $vaccineId,
                        'total_stock' => $totalStock,
                        'available_stock' => $availableStock,
                        'used_stock' => $totalStock - $availableStock,
                        'expiration_date' => $expirationDate,
                    ]);

                    $stockCount++;
                }
            }
        }
    }
}
