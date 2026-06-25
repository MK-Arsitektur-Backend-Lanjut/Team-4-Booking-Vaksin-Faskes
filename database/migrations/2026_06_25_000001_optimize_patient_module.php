<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $result = DB::selectOne(
                "SELECT COUNT(1) as c FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$indexName]
            );
        } else {
            $dbName = DB::getDatabaseName();
            $result = DB::selectOne(
                "SELECT COUNT(1) as c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$dbName, $table, $indexName]
            );
        }

        return !empty($result) && ($result->c ?? 0) > 0;
    }

    public function up(): void
    {
        // 1. Soft deletes for patients
        if (Schema::hasTable('patients') && !Schema::hasColumn('patients', 'deleted_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 2. Covering index for bookings query: WHERE patient_id = ? ORDER BY booked_at DESC
        if (Schema::hasTable('bookings') && !$this->indexExists('bookings', 'idx_bookings_patient_booked')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index(['patient_id', 'booked_at'], 'idx_bookings_patient_booked');
            });
        }

        // 3. Drop redundant index on health_histeries (patient_id, condition_name)
        //    Already covered by idx_hh_patient_condition_diagnosed (patient_id, condition_name, diagnosed_at DESC)
        if (Schema::hasTable('health_histories') && $this->indexExists('health_histories', 'health_histories_patient_id_condition_name_index')) {
            Schema::table('health_histories', function (Blueprint $table) {
                $table->dropIndex('health_histories_patient_id_condition_name_index');
            });
        }

        // 4. Drop unused phone_number index on patients
        if (Schema::hasTable('patients') && $this->indexExists('patients', 'idx_patients_phone_number')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropIndex('idx_patients_phone_number');
            });
        }
    }

    public function down(): void
    {
        // Reverse soft deletes
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'deleted_at')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        // Drop the booking index we added
        if (Schema::hasTable('bookings') && $this->indexExists('bookings', 'idx_bookings_patient_booked')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex('idx_bookings_patient_booked');
            });
        }

        // Restore phone_number index
        if (Schema::hasTable('patients') && !$this->indexExists('patients', 'idx_patients_phone_number')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->index('phone_number', 'idx_patients_phone_number');
            });
        }

        // Restore health_histories index
        if (Schema::hasTable('health_histories') && !$this->indexExists('health_histories', 'health_histories_patient_id_condition_name_index')) {
            Schema::table('health_histories', function (Blueprint $table) {
                $table->index(['patient_id', 'condition_name'], 'health_histories_patient_id_condition_name_index');
            });
        }
    }
};
