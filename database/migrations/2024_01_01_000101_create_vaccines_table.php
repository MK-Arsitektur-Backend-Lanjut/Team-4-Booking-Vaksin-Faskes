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
        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama vaksin (Pfizer, Moderna, AZ, Sinovac, dll)
            $table->string('code')->unique(); // Kode vaksin
            $table->text('description')->nullable();
            $table->integer('doses_required')->default(2); // Jumlah dosis yang diperlukan
            $table->integer('days_between_doses')->nullable(); // Jarak hari antar dosis
            $table->string('manufacturer')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccines');
    }
};
