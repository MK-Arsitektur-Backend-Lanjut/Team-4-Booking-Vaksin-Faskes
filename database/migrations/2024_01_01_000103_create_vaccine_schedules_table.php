<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vaccine_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_center_id')->constrained('health_centers')->onDelete('cascade');
            $table->foreignId('vaccine_id')->constrained('vaccines')->onDelete('cascade');
            $table->date('schedule_date'); // Tanggal jadwal
            $table->time('start_time'); // Jam mulai
            $table->time('end_time'); // Jam selesai
            $table->integer('quota'); // Total kuota slot untuk hari ini
            $table->integer('available_quota'); // Kuota tersisa
            $table->integer('booked_quota')->default(0); // Kuota yang sudah dipesan
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            
            // Index for better query performance
            $table->index(['schedule_date', 'health_center_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccine_schedules');
    }
};
