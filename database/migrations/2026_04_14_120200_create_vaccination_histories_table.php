<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccination_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('vaccine_name');
            $table->unsignedTinyInteger('dose_number');
            $table->timestamp('vaccinated_at');
            $table->string('provider_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'vaccinated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccination_histories');
    }
};
