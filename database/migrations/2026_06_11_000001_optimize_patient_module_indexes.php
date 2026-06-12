<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optimize indexes on child/transactional tables only.
 *
 * Constraints:
 *  - No indexes on master data tables (patients)
 *  - No indexes on unique / ID columns
 *  - Focus on non-unique columns used in WHERE + ORDER BY query patterns
 */
return new class extends Migration
{
    public function up(): void
    {
        $createIndexIfNotExists = function (string $table, string $indexName, string $columns) {
            $dbName = DB::getDatabaseName();
            $count = DB::selectOne(
                "SELECT COUNT(1) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$dbName, $table, $indexName]
            );
            if (empty($count) || ($count->c ?? 0) == 0) {
                DB::statement("CREATE INDEX {$indexName} ON `{$table}` ({$columns})");
            }
        };

        // ───────────────────────────────────────────────────────────
        // health_histories — child table of patients
        // ───────────────────────────────────────────────────────────
        //
        // Query pattern (getByPatient):
        //   SELECT * FROM health_histories
        //   WHERE patient_id = ? ORDER BY diagnosed_at DESC, id DESC
        //
        // Existing index: (patient_id, condition_name) — does NOT cover the ORDER BY.
        // New index covers the WHERE + ORDER BY in a single B-tree scan.
        if (Schema::hasTable('health_histories')
            && Schema::hasColumn('health_histories', 'patient_id')
            && Schema::hasColumn('health_histories', 'diagnosed_at')
        ) {
            $createIndexIfNotExists(
                'health_histories',
                'idx_hh_patient_diagnosed',
                '`patient_id`, `diagnosed_at` DESC'
            );
        }

        // Additional covering index for condition filtering + date ordering.
        // Useful for future queries that filter by condition_name and sort by date.
        if (Schema::hasTable('health_histories')
            && Schema::hasColumn('health_histories', 'patient_id')
            && Schema::hasColumn('health_histories', 'condition_name')
            && Schema::hasColumn('health_histories', 'diagnosed_at')
        ) {
            $createIndexIfNotExists(
                'health_histories',
                'idx_hh_patient_condition_diagnosed',
                '`patient_id`, `condition_name`, `diagnosed_at` DESC'
            );
        }

        // ───────────────────────────────────────────────────────────
        // vaccination_histories — child table of patients
        // ───────────────────────────────────────────────────────────
        //
        // Existing index: (patient_id, vaccinated_at) — already covers getByPatient.
        //
        // New composite index for vaccine-specific lookups and dose tracking.
        // Covers queries like: WHERE patient_id = ? AND vaccine_name = ?
        // Also useful for deduplication checks (same vaccine + dose for a patient).
        if (Schema::hasTable('vaccination_histories')
            && Schema::hasColumn('vaccination_histories', 'patient_id')
            && Schema::hasColumn('vaccination_histories', 'vaccine_name')
            && Schema::hasColumn('vaccination_histories', 'dose_number')
        ) {
            $createIndexIfNotExists(
                'vaccination_histories',
                'idx_vh_patient_vaccine_dose',
                '`patient_id`, `vaccine_name`, `dose_number`'
            );
        }

        // Composite index for provider-based lookups.
        // Covers: WHERE patient_id = ? AND provider_name = ?
        // Useful for reports by provider (faskes) and patient history at a specific provider.
        if (Schema::hasTable('vaccination_histories')
            && Schema::hasColumn('vaccination_histories', 'patient_id')
            && Schema::hasColumn('vaccination_histories', 'provider_name')
            && Schema::hasColumn('vaccination_histories', 'vaccinated_at')
        ) {
            $createIndexIfNotExists(
                'vaccination_histories',
                'idx_vh_patient_provider_date',
                '`patient_id`, `provider_name`, `vaccinated_at` DESC'
            );
        }
    }

    public function down(): void
    {
        $dropIndexIfExists = function (string $table, string $indexName) {
            $dbName = DB::getDatabaseName();
            $count = DB::selectOne(
                "SELECT COUNT(1) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$dbName, $table, $indexName]
            );
            if (!empty($count) && ($count->c ?? 0) > 0) {
                DB::statement("DROP INDEX {$indexName} ON `{$table}`");
            }
        };

        $dropIndexIfExists('health_histories', 'idx_hh_patient_diagnosed');
        $dropIndexIfExists('health_histories', 'idx_hh_patient_condition_diagnosed');
        $dropIndexIfExists('vaccination_histories', 'idx_vh_patient_vaccine_dose');
        $dropIndexIfExists('vaccination_histories', 'idx_vh_patient_provider_date');
    }
};
