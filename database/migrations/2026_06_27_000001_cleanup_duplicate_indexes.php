<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        if (Schema::hasTable('patients')) {
            $duplicates = [
                'idx_patients_patient_id',        // duplicate of UNIQUE(patient_id)
                'idx_patients_full_name',          // duplicate of patients_full_name_index
                'idx_patients_identity_status',    // duplicate of patients_identity_verification_status_index
            ];

            foreach ($duplicates as $index) {
                if ($indexExists('patients', $index)) {
                    DB::statement("DROP INDEX {$index} ON patients");
                }
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

        if (Schema::hasTable('patients')) {
            if (!$indexExists('patients', 'idx_patients_patient_id')) {
                DB::statement('CREATE INDEX idx_patients_patient_id ON patients (`patient_id`(32))');
            }
            if (!$indexExists('patients', 'idx_patients_full_name')) {
                DB::statement('CREATE INDEX idx_patients_full_name ON patients (`full_name`)');
            }
            if (!$indexExists('patients', 'idx_patients_identity_status')) {
                DB::statement('CREATE INDEX idx_patients_identity_status ON patients (`identity_verification_status`)');
            }
        }
    }
};
