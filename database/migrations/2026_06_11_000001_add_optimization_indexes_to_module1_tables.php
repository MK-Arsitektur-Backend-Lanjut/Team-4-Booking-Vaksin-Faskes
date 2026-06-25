<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes to Module 1 tables.
     *
     * These indexes optimize the most common query patterns:
     * - Filter by province/city/status on health_centers
     * - Search by name/code on health_centers and vaccines
     * - Filter available stocks and schedules
     * - Composite lookups on vaccine_stocks and vaccine_schedules
     */
    public function up(): void
    {
        Schema::table('health_centers', function (Blueprint $table) {
            $table->index('province', 'idx_hc_province');
            $table->index('city', 'idx_hc_city');
            $table->index('status', 'idx_hc_status');
            $table->index(['province', 'city'], 'idx_hc_province_city');
        });

        Schema::table('vaccines', function (Blueprint $table) {
            $table->index('status', 'idx_vac_status');
        });

        Schema::table('vaccine_stocks', function (Blueprint $table) {
            $table->index('available_stock', 'idx_vs_available_stock');
            $table->index(['health_center_id', 'vaccine_id'], 'idx_vs_hc_vaccine');
            $table->index(['available_stock', 'expiration_date'], 'idx_vs_available_expiry');
        });

        Schema::table('vaccine_schedules', function (Blueprint $table) {
            $table->index('status', 'idx_vsch_status');
            $table->index('available_quota', 'idx_vsch_available_quota');
            $table->index(['schedule_date', 'status', 'available_quota'], 'idx_vsch_date_status_quota');
            $table->index(['health_center_id', 'vaccine_id', 'schedule_date'], 'idx_vsch_hc_vac_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_centers', function (Blueprint $table) {
            $table->dropIndex('idx_hc_province');
            $table->dropIndex('idx_hc_city');
            $table->dropIndex('idx_hc_status');
            $table->dropIndex('idx_hc_province_city');
        });

        Schema::table('vaccines', function (Blueprint $table) {
            $table->dropIndex('idx_vac_status');
        });

        Schema::table('vaccine_stocks', function (Blueprint $table) {
            $table->dropIndex('idx_vs_available_stock');
            $table->dropIndex('idx_vs_hc_vaccine');
            $table->dropIndex('idx_vs_available_expiry');
        });

        Schema::table('vaccine_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_vsch_status');
            $table->dropIndex('idx_vsch_available_quota');
            $table->dropIndex('idx_vsch_date_status_quota');
            $table->dropIndex('idx_vsch_hc_vac_date');
        });
    }
};
