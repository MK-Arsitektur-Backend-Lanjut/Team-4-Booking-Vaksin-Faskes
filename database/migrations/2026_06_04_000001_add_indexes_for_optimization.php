<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::getPdo();

        // Helper to create index only if it doesn't exist
        $createIndexIfNotExists = function (string $table, string $indexName, string $columns) use ($connection) {
            $dbName = DB::getDatabaseName();
            $count = DB::selectOne("SELECT COUNT(1) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?", [$dbName, $table, $indexName]);
            if (empty($count) || ($count->c ?? 0) == 0) {
                DB::statement("CREATE INDEX {$indexName} ON `{$table}` ({$columns})");
            }
        };

        // Patients indexes
        if (Schema::hasTable('patients')) {
            if (Schema::hasColumn('patients', 'patient_id')) {
                $createIndexIfNotExists('patients', 'idx_patients_patient_id', '`patient_id`(32)');
            }
            if (Schema::hasColumn('patients', 'full_name')) {
                $createIndexIfNotExists('patients', 'idx_patients_full_name', '`full_name`');
            }
            if (Schema::hasColumn('patients', 'phone_number')) {
                $createIndexIfNotExists('patients', 'idx_patients_phone_number', '`phone_number`');
            }
            if (Schema::hasColumn('patients', 'identity_verification_status')) {
                $createIndexIfNotExists('patients', 'idx_patients_identity_status', '`identity_verification_status`');
            }
        }

        // Bookings indexes
        if (Schema::hasTable('bookings')) {
            if (Schema::hasColumn('bookings', 'schedule_id') && Schema::hasColumn('bookings', 'status')) {
                $createIndexIfNotExists('bookings', 'idx_bookings_schedule_status', '`schedule_id`,`status`');
            }
            if (Schema::hasColumn('bookings', 'patient_id') && Schema::hasColumn('bookings', 'status')) {
                $createIndexIfNotExists('bookings', 'idx_bookings_patient_status', '`patient_id`,`status`');
            }
        }

        // Schedules indexes
        if (Schema::hasTable('schedules')) {
            if (Schema::hasColumn('schedules', 'faskes_id') && Schema::hasColumn('schedules', 'starts_at')) {
                $createIndexIfNotExists('schedules', 'idx_schedules_faskes_starts_at', '`faskes_id`,`starts_at`');
            }
            if (Schema::hasColumn('schedules', 'starts_at')) {
                $createIndexIfNotExists('schedules', 'idx_schedules_starts_at', '`starts_at`');
            }
        }

        // Vaccine stocks / schedules common lookups
        if (Schema::hasTable('vaccine_stocks')) {
            if (Schema::hasColumn('vaccine_stocks', 'health_center_id')) {
                $createIndexIfNotExists('vaccine_stocks', 'idx_vaccine_stocks_health_center', '`health_center_id`');
            }
            if (Schema::hasColumn('vaccine_stocks', 'vaccine_id')) {
                $createIndexIfNotExists('vaccine_stocks', 'idx_vaccine_stocks_vaccine', '`vaccine_id`');
            }
        }
    }

    public function down(): void
    {
        // Do not drop indexes automatically to avoid accidental data-impact on rollback.
    }
};
