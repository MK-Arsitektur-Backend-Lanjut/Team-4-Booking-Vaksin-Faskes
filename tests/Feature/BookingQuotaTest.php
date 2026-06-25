<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Covers the concurrency-safety contract of the booking write path:
 * quota is never oversold, queue numbers are sequential and never reused,
 * duplicates are rejected cleanly (422, not 500), and cancelling frees a slot.
 */
class BookingQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function scheduleWithQuota(int $quota): Schedule
    {
        return Schedule::factory()->create(['quota' => $quota]);
    }

    private function book(int $scheduleId, int $patientId): TestResponse
    {
        return $this->postJson('/api/v1/bookings', [
            'schedule_id' => $scheduleId,
            'patient_id' => $patientId,
        ]);
    }

    public function test_successful_booking_increments_booked_count_and_assigns_first_queue_number(): void
    {
        $schedule = $this->scheduleWithQuota(5);
        $patient = Patient::factory()->create();

        $response = $this->book($schedule->id, $patient->id);

        $response->assertStatus(201);
        $this->assertEquals(1, $response->json('data.queue_number'));
        $this->assertSame(1, $schedule->refresh()->booked_count);
    }

    public function test_quota_cannot_be_oversold(): void
    {
        $schedule = $this->scheduleWithQuota(2);
        $patients = Patient::factory()->count(3)->create();

        $this->book($schedule->id, $patients[0]->id)->assertStatus(201);
        $this->book($schedule->id, $patients[1]->id)->assertStatus(201);
        // The third booking exceeds the quota of 2.
        $this->book($schedule->id, $patients[2]->id)->assertStatus(422);

        $this->assertSame(2, $schedule->refresh()->booked_count);
        $this->assertSame(2, $schedule->bookings()->count());
    }

    public function test_duplicate_patient_booking_is_rejected_with_422(): void
    {
        $schedule = $this->scheduleWithQuota(5);
        $patient = Patient::factory()->create();

        $this->book($schedule->id, $patient->id)->assertStatus(201);
        $this->book($schedule->id, $patient->id)->assertStatus(422);

        $this->assertSame(1, $schedule->refresh()->booked_count);
        $this->assertSame(1, $schedule->bookings()->count());
    }

    public function test_queue_numbers_are_sequential_and_unique(): void
    {
        $schedule = $this->scheduleWithQuota(5);
        $patients = Patient::factory()->count(3)->create();

        $q1 = $this->book($schedule->id, $patients[0]->id)->json('data.queue_number');
        $q2 = $this->book($schedule->id, $patients[1]->id)->json('data.queue_number');
        $q3 = $this->book($schedule->id, $patients[2]->id)->json('data.queue_number');

        $this->assertEquals([1, 2, 3], [$q1, $q2, $q3]);
    }

    public function test_cancelling_a_booking_frees_a_slot_without_reusing_the_queue_number(): void
    {
        $schedule = $this->scheduleWithQuota(1);
        $patients = Patient::factory()->count(2)->create();

        $first = $this->book($schedule->id, $patients[0]->id);
        $first->assertStatus(201);

        // The single slot is now taken.
        $this->book($schedule->id, $patients[1]->id)->assertStatus(422);
        $this->assertSame(1, $schedule->refresh()->booked_count);

        // Cancelling frees the slot.
        $bookingId = $first->json('data.id');
        $this->patchJson("/api/v1/bookings/{$bookingId}/cancel", [
            'cancellation_reason' => 'changed plans',
        ])->assertStatus(200);
        $this->assertSame(0, $schedule->refresh()->booked_count);

        // The second patient can now book; the queue number advances (no reuse).
        $second = $this->book($schedule->id, $patients[1]->id);
        $second->assertStatus(201);
        $this->assertSame(1, $schedule->refresh()->booked_count);
        $this->assertEquals(2, $second->json('data.queue_number'));
    }
}
