<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('full_name');
            $table->date('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->enum('identity_verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('identity_verified_at')->nullable();
            $table->timestamps();

            $table->index('full_name');
            $table->index('identity_verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
