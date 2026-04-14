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
        Schema::create('practitioners', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('name');
            $table->string('satusehat_id')->nullable()->index(); // ID dari SATUSEHAT (contoh: P000123)
            $table->string('ihs_number')->nullable();
            $table->string('sip')->nullable(); // Nomor Izin Praktik
            $table->enum('profession_type', ['doctor', 'nurse', 'midwife', 'pharmacist', 'other'])->default('doctor');
            $table->boolean('is_active')->default(true);
            $table->decimal('fee', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practitioners');
    }
};
