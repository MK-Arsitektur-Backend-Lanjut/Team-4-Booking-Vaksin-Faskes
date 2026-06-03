<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('patient_id', 40)->nullable()->after('id')->unique();
        });

        Schema::table('faskes', function (Blueprint $table) {
            $table->string('faskes_id', 40)->nullable()->after('id')->unique();
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->string('schedule_id', 40)->nullable()->after('id')->unique();
        });

        Schema::table('health_histories', function (Blueprint $table) {
            $table->string('health_history_id', 40)->nullable()->after('id')->unique();
        });

        Schema::table('vaccination_histories', function (Blueprint $table) {
            $table->string('vaccination_history_id', 40)->nullable()->after('id')->unique();
        });
    }

    public function down(): void
    {
        Schema::table('vaccination_histories', function (Blueprint $table) {
            $table->dropUnique(['vaccination_history_id']);
            $table->dropColumn('vaccination_history_id');
        });

        Schema::table('health_histories', function (Blueprint $table) {
            $table->dropUnique(['health_history_id']);
            $table->dropColumn('health_history_id');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropUnique(['schedule_id']);
            $table->dropColumn('schedule_id');
        });

        Schema::table('faskes', function (Blueprint $table) {
            $table->dropUnique(['faskes_id']);
            $table->dropColumn('faskes_id');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};
