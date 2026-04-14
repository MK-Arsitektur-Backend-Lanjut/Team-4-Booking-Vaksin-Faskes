<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faskes_id')->constrained('faskes')->cascadeOnDelete();
            $table->string('service_type')->default('vaccination');
            $table->string('vaccine_name')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('booked_count')->default(0);
            $table->timestamps();

            $table->index(['faskes_id', 'starts_at']);
            $table->index('service_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
