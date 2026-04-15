<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Patient;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Seed 10,000+ bookings distributed across schedules.
     */
    public function run(): void
    {
        $scheduleIds = Schedule::pluck('id')->toArray();
        $patientIds = Patient::pluck('id')->toArray();

        // Track queue numbers per schedule
        $queueCounters = [];
        // Track which patients are already booked for each schedule
        $bookedPairs = [];

        $statuses = ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled'];
        $totalBookings = 10000;
        $batchSize = 500;
        $bookings = [];

        for ($i = 0; $i < $totalBookings; $i++) {
            // Pick a random schedule and patient, ensuring no duplicates
            $attempts = 0;
            do {
                $scheduleId = fake()->randomElement($scheduleIds);
                $patientId = fake()->randomElement($patientIds);
                $pairKey = "{$scheduleId}-{$patientId}";
                $attempts++;

                // Safety valve: if we can't find a unique pair after 20 tries, skip
                if ($attempts > 20) {
                    break;
                }
            } while (isset($bookedPairs[$pairKey]));

            if ($attempts > 20) {
                continue;
            }

            $bookedPairs[$pairKey] = true;

            // Increment queue number for this schedule
            if (! isset($queueCounters[$scheduleId])) {
                $queueCounters[$scheduleId] = 0;
            }
            $queueCounters[$scheduleId]++;

            $status = fake()->randomElement($statuses);
            $bookedAt = fake()->dateTimeBetween('-1 month', 'now');

            $bookings[] = [
                'schedule_id' => $scheduleId,
                'patient_id' => $patientId,
                'queue_number' => $queueCounters[$scheduleId],
                'status' => $status,
                'booked_at' => $bookedAt->format('Y-m-d H:i:s'),
                'checked_in_at' => in_array($status, ['checked_in', 'completed'])
                    ? Carbon::instance($bookedAt)->addMinutes(rand(10, 120))->format('Y-m-d H:i:s')
                    : null,
                'completed_at' => $status === 'completed'
                    ? Carbon::instance($bookedAt)->addMinutes(rand(60, 240))->format('Y-m-d H:i:s')
                    : null,
                'cancelled_at' => $status === 'cancelled'
                    ? Carbon::instance($bookedAt)->addMinutes(rand(5, 60))->format('Y-m-d H:i:s')
                    : null,
                'cancellation_reason' => $status === 'cancelled' ? fake()->sentence() : null,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            // Insert in batches
            if (count($bookings) >= $batchSize) {
                Booking::insert($bookings);
                $bookings = [];
            }
        }

        // Insert remaining
        if (! empty($bookings)) {
            Booking::insert($bookings);
        }
    }
}
