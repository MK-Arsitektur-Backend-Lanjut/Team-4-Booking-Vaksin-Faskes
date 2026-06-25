<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the Queue & Appointment module.
 *
 * These are additive (no data change) and target the hot query paths exercised
 * by the API under load. They complement the existing keys created by the
 * original migrations:
 *   - bookings: PRIMARY(id), UNIQUE(schedule_id, patient_id), UNIQUE(schedule_id, queue_number)
 *               + the auto FK index on patient_id
 *   - schedules: PRIMARY(id) + auto FK indexes on health_center_id, vaccine_id
 *
 * Note: the composite indexes below intentionally lead with an FK column
 * (patient_id / health_center_id), overlapping the auto FK index on their left
 * prefix. The trailing column (booked_at / date) adds the sort/range coverage
 * the single-column FK index lacks. The FK indexes are kept in place (simpler &
 * safe) — they still back the foreign keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // getQuotaUsage()  -> WHERE schedule_id = ? AND status NOT IN ('cancelled') COUNT(*)
            // Correlated withCount() subquery in Schedule::findAvailable() (same predicate).
            // findBySchedule() -> WHERE schedule_id = ? AND status = ?  (only when a status filter
            //   is supplied; its ORDER BY queue_number is already served by the existing
            //   UNIQUE(schedule_id, queue_number), not by this index).
            $table->index(['schedule_id', 'status'], 'bookings_schedule_id_status_index');

            // findByPatient() -> WHERE patient_id = ? ORDER BY booked_at DESC
            // Composite avoids a filesort on booked_at for a patient's history.
            $table->index(['patient_id', 'booked_at'], 'bookings_patient_id_booked_at_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            // findByHealthCenter() -> WHERE health_center_id = ? ORDER BY date, start_time
            // findAvailable()      -> WHERE health_center_id = ? [AND date = ?] ORDER BY date
            $table->index(['health_center_id', 'date'], 'schedules_health_center_id_date_index');

            // findAvailable() with a date filter only -> WHERE date = ? ORDER BY date
            $table->index('date', 'schedules_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_schedule_id_status_index');
            $table->dropIndex('bookings_patient_id_booked_at_index');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_health_center_id_date_index');
            $table->dropIndex('schedules_date_index');
        });
    }
};
