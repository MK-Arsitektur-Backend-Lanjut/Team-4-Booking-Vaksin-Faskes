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
        Schema::create('health_centers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama Faskes
            $table->string('code')->unique(); // Kode identifikasi Faskes
            $table->string('address'); // Alamat
            $table->string('province'); // Provinsi
            $table->string('city'); // Kota
            $table->string('district'); // Kecamatan
            $table->string('village'); // Kelurahan
            $table->decimal('latitude', 10, 8)->nullable(); // Koordinat
            $table->decimal('longitude', 11, 8)->nullable(); // Koordinat
            $table->string('phone')->nullable();
            $table->integer('capacity')->default(100); // Kapasitas harian
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_centers');
    }
};
