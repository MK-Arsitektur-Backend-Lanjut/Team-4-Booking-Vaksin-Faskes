<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (testing) doesn't need these optimization indexes, skip
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $dbName = DB::getDatabaseName();

        // Check if a column already has a single-column UNIQUE index (skip duplicate)
        $columnHasUniqueIndex = function (string $table, string $column) use ($dbName): bool {
            $count = DB::selectOne(
                "SELECT COUNT(1) as c FROM information_schema.STATISTICS s1
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                 AND COLUMN_NAME = ? AND NON_UNIQUE = 0
                 AND SEQ_IN_INDEX = 1
                 AND (SELECT COUNT(*) FROM information_schema.STATISTICS s2
                      WHERE s2.TABLE_SCHEMA = s1.TABLE_SCHEMA
                      AND s2.TABLE_NAME = s1.TABLE_NAME
                      AND s2.INDEX_NAME = s1.INDEX_NAME) = 1",
                [$dbName, $table, $column]
            );
            return !empty($count) && ($count->c ?? 0) > 0;
        };

        // Helper to create index only if it doesn't exist and column isn't already UNIQUE-indexed
        $createIndexIfNotExists = function (string $table, string $indexName, string $column, string $columns) use ($dbName, $columnHasUniqueIndex) {
            if ($columnHasUniqueIndex($table, $column)) {
                return; // UNIQUE constraint already provides an index — skip
            }
            $count = DB::selectOne("SELECT COUNT(1) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?", [$dbName, $table, $indexName]);
            if (empty($count) || ($count->c ?? 0) == 0) {
                DB::statement("CREATE INDEX {$indexName} ON `{$table}` ({$columns})");
            }
        };

        // Patients indexes
        if (Schema::hasTable('patients')) {
            if (Schema::hasColumn('patients', 'patient_id')) {
                $createIndexIfNotExists('patients', 'idx_patients_patient_id', 'patient_id', '`patient_id`(32)');
            }
            if (Schema::hasColumn('patients', 'full_name')) {
                $createIndexIfNotExists('patients', 'idx_patients_full_name', 'full_name', '`full_name`');
            }
            if (Schema::hasColumn('patients', 'phone_number')) {
                $createIndexIfNotExists('patients', 'idx_patients_phone_number', 'phone_number', '`phone_number`');
            }
            if (Schema::hasColumn('patients', 'identity_verification_status')) {
                $createIndexIfNotExists('patients', 'idx_patients_identity_status', 'identity_verification_status', '`identity_verification_status`');
            }
        }

        // Bookings indexes
        if (Schema::hasTable('bookings')) {
            if (Schema::hasColumn('bookings', 'schedule_id') && Schema::hasColumn('bookings', 'status')) {
                $createIndexIfNotExists('bookings', 'idx_bookings_schedule_status', 'schedule_id', '`schedule_id`,`status`');
            }
            if (Schema::hasColumn('bookings', 'patient_id') && Schema::hasColumn('bookings', 'status')) {
                $createIndexIfNotExists('bookings', 'idx_bookings_patient_status', 'patient_id', '`patient_id`,`status`');
            }
        }

        // Schedules indexes
        if (Schema::hasTable('schedules')) {
            if (Schema::hasColumn('schedules', 'faskes_id') && Schema::hasColumn('schedules', 'starts_at')) {
                $createIndexIfNotExists('schedules', 'idx_schedules_faskes_starts_at', 'faskes_id', '`faskes_id`,`starts_at`');
            }
            if (Schema::hasColumn('schedules', 'starts_at')) {
                $createIndexIfNotExists('schedules', 'idx_schedules_starts_at', 'starts_at', '`starts_at`');
            }
        }

        // Vaccine stocks / schedules common lookups
        if (Schema::hasTable('vaccine_stocks')) {
            if (Schema::hasColumn('vaccine_stocks', 'health_center_id')) {
                $createIndexIfNotExists('vaccine_stocks', 'idx_vaccine_stocks_health_center', 'health_center_id', '`health_center_id`');
            }
            if (Schema::hasColumn('vaccine_stocks', 'vaccine_id')) {
                $createIndexIfNotExists('vaccine_stocks', 'idx_vaccine_stocks_vaccine', 'vaccine_id', '`vaccine_id`');
            }
        }
    }

    public function down(): void
    {
        // Do not drop indexes automatically to avoid accidental data-impact on rollback.
    }
};
