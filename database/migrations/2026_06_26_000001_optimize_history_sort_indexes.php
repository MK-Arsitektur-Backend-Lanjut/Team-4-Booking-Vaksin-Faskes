<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Optimize ORDER BY clauses by adding `id DESC` to existing descending indexes.
 *
 * Problem:
 *   getByPatient() queries use ORDER BY {date} DESC, id DESC
 *   Existing indexes only cover {date} DESC — missing id DESC tiebreaker.
 *   MySQL resorts to filesort when multiple records share the same date.
 *
 * Fix:
 *   Replace the old index with one that includes id DESC as the last column,
 *   making it a fully covering index for the ORDER BY clause.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $dbName = DB::getDatabaseName();

        $indexExists = function (string $table, string $indexName) use ($dbName): bool {
            $count = DB::selectOne(
                "SELECT COUNT(1) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$dbName, $table, $indexName]
            );
            return !empty($count) && ($count->c ?? 0) > 0;
        };

        // ───────────────────────────────────────────────────────────
        // health_histories
        // Old: idx_hh_patient_diagnosed (patient_id, diagnosed_at DESC)
        // New: idx_hh_patient_diagnosed_id (patient_id, diagnosed_at DESC, id DESC)
        // ───────────────────────────────────────────────────────────
        if (Schema::hasTable('health_histories')) {
            if ($indexExists('health_histories', 'idx_hh_patient_diagnosed')) {
                DB::statement('DROP INDEX idx_hh_patient_diagnosed ON health_histories');
            }
            if (!$indexExists('health_histories', 'idx_hh_patient_diagnosed_id')) {
                DB::statement('CREATE INDEX idx_hh_patient_diagnosed_id ON health_histories (`patient_id`, `diagnosed_at` DESC, `id` DESC)');
            }
        }

        // ───────────────────────────────────────────────────────────
        // vaccination_histories
        // Old: vaccination_histories_patient_id_vaccinated_at_index (patient_id, vaccinated_at)
        // New: idx_vh_patient_vaccinated_id (patient_id, vaccinated_at DESC, id DESC)
        // ───────────────────────────────────────────────────────────
        if (Schema::hasTable('vaccination_histories')) {
            if ($indexExists('vaccination_histories', 'vaccination_histories_patient_id_vaccinated_at_index')) {
                DB::statement('DROP INDEX vaccination_histories_patient_id_vaccinated_at_index ON vaccination_histories');
            }
            if (!$indexExists('vaccination_histories', 'idx_vh_patient_vaccinated_id')) {
                DB::statement('CREATE INDEX idx_vh_patient_vaccinated_id ON vaccination_histories (`patient_id`, `vaccinated_at` DESC, `id` DESC)');
            }
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $dbName = DB::getDatabaseName();

        $indexExists = function (string $table, string $indexName) use ($dbName): bool {
            $count = DB::selectOne(
                "SELECT COUNT(1) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$dbName, $table, $indexName]
            );
            return !empty($count) && ($count->c ?? 0) > 0;
        };

        // Restore health_histories index
        if (Schema::hasTable('health_histories')) {
            if ($indexExists('health_histories', 'idx_hh_patient_diagnosed_id')) {
                DB::statement('DROP INDEX idx_hh_patient_diagnosed_id ON health_histories');
            }
            if (!$indexExists('health_histories', 'idx_hh_patient_diagnosed')) {
                DB::statement('CREATE INDEX idx_hh_patient_diagnosed ON health_histories (`patient_id`, `diagnosed_at` DESC)');
            }
        }

        // Restore vaccination_histories index
        if (Schema::hasTable('vaccination_histories')) {
            if ($indexExists('vaccination_histories', 'idx_vh_patient_vaccinated_id')) {
                DB::statement('DROP INDEX idx_vh_patient_vaccinated_id ON vaccination_histories');
            }
            if (!$indexExists('vaccination_histories', 'vaccination_histories_patient_id_vaccinated_at_index')) {
                DB::statement('CREATE INDEX vaccination_histories_patient_id_vaccinated_at_index ON vaccination_histories (`patient_id`, `vaccinated_at`)');
            }
        }
    }
};
