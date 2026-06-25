<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add patient_id and schedule_id external columns (string) and backfill
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'patient_id')) {
                $table->string('patient_id')->nullable()->after('id');
                $table->unique('patient_id');
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'schedule_id')) {
                $table->string('schedule_id')->nullable()->after('id');
                $table->unique('schedule_id');
            }
        });

        // Backfill from numeric id if values not present
        DB::table('patients')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $r) {
                $existing = DB::table('patients')->where('id', $r->id)->value('patient_id');
                if (empty($existing)) {
                    $code = sprintf('PAT-%06d', $r->id);
                    DB::table('patients')->where('id', $r->id)->update(['patient_id' => $code]);
                }
            }
        });

        DB::table('schedules')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $r) {
                $existing = DB::table('schedules')->where('id', $r->id)->value('schedule_id');
                if (empty($existing)) {
                    $code = sprintf('SCH-%06d', $r->id);
                    DB::table('schedules')->where('id', $r->id)->update(['schedule_id' => $code]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'patient_id')) {
                $table->dropUnique(['patient_id']);
                $table->dropColumn('patient_id');
            }
        });

        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'schedule_id')) {
                $table->dropUnique(['schedule_id']);
                $table->dropColumn('schedule_id');
            }
        });
    }
};
