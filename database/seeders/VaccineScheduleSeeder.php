<?php

namespace Database\Seeders;

use App\Models\VaccineSchedule;
use App\Models\HealthCenter;
use App\Models\Vaccine;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class VaccineScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $healthCenters = HealthCenter::pluck('id')->toArray();
        $vaccines = Vaccine::pluck('id')->toArray();

        // Fixed time slots to avoid duplicates
        $timeSlots = [
            ['08:00', '12:00'],
            ['13:00', '17:00'],
        ];

        // Create schedules for next 7 days only (to keep data manageable)
        for ($day = 1; $day <= 7; $day++) {
            $scheduleDate = Carbon::now()->addDays($day);

            foreach ($healthCenters as $healthCenterId) {
                foreach ($vaccines as $vaccineId) {
                    // Only 2 time slots per day per vaccine
                    foreach ($timeSlots as $timeSlot) {
                        $quota = mt_rand(20, 150);
                        $bookedQuota = mt_rand(0, $quota);
                        $availableQuota = $quota - $bookedQuota;

                        VaccineSchedule::create([
                            'health_center_id' => $healthCenterId,
                            'vaccine_id' => $vaccineId,
                            'schedule_date' => $scheduleDate,
                            'start_time' => $timeSlot[0],
                            'end_time' => $timeSlot[1],
                            'quota' => $quota,
                            'available_quota' => $availableQuota,
                            'booked_quota' => $bookedQuota,
                            'notes' => 'Regular vaccination schedule',
                            'status' => 'scheduled',
                        ]);
                    }
                }
            }
        }
    }
}
