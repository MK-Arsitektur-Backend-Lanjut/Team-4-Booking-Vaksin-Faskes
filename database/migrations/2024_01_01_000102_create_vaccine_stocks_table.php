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
        Schema::create('vaccine_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_center_id')->constrained('health_centers')->onDelete('cascade');
            $table->foreignId('vaccine_id')->constrained('vaccines')->onDelete('cascade');
            $table->integer('total_stock'); // Total stok
            $table->integer('available_stock'); // Stok tersedia
            $table->integer('used_stock')->default(0); // Stok terpakai
            $table->date('expiration_date'); // Tanggal kadaluarsa
            $table->timestamps();
            
            // Index for better query performance, allowing multiple batches per date
            $table->index(['health_center_id', 'vaccine_id', 'expiration_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccine_stocks');
    }
};
