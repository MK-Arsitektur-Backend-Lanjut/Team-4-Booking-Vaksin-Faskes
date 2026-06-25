<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Maintained counters on `schedules` that let the booking write path reserve a
 * quota slot and allocate a queue number in one atomic UPDATE, instead of a wide
 * `lockForUpdate` + COUNT(*) + MAX(queue_number) that serialized every booking
 * for a schedule. `booked_count` mirrors the number of non-cancelled bookings;
 * `last_queue_number` is monotonic (never decremented) so queue numbers are
 * never reused even after a cancellation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedInteger('booked_count')->default(0)->after('quota');
            $table->unsignedInteger('last_queue_number')->default(0)->after('booked_count');
        });

        // Backfill so the counters agree with any bookings that already exist.
        DB::statement(
            'UPDATE schedules SET booked_count = (
                SELECT COUNT(*) FROM bookings
                WHERE bookings.schedule_id = schedules.id AND bookings.status <> ?
            )',
            ['cancelled']
        );

        DB::statement(
            'UPDATE schedules SET last_queue_number = (
                SELECT COALESCE(MAX(queue_number), 0) FROM bookings
                WHERE bookings.schedule_id = schedules.id
            )'
        );
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['booked_count', 'last_queue_number']);
        });
    }
};
