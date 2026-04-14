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
        Schema::create('out_patient_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outpatient_visit_id')->constrained()->onDelete('cascade');
            $table->string('icd10_code');     // Kode dari tabel ICD-10 lokal Anda
            $table->string('icd10_display');  // Teks deskripsi diagnosis
            $table->boolean('is_primary')->default(false); 
            $table->string('satusehat_condition_id')->nullable(); // ID dari SatuSehat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('out_patient_diagnoses');
    }
};
