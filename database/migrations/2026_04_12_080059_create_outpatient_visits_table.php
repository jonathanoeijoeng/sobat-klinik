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
        Schema::create('outpatient_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->index()->constrained('clinics');
            $table->string('visit_number')->unique();
            // Relasi Dasar
            $table->foreignId('patient_id')->index();
            $table->foreignId('practitioner_id')->index();
            $table->foreignId('location_id')->index(); // Poli

            // Status Alur Klinik
            $table->enum('status', ['arrived', 'in-progress', 'pharmacy', 'finished', 'cancelled'])->default('arrive');
            $table->enum('internal_status', ['arrived', 'at_practitioner', 'sent_to_pharmacy', 'sent_for_payment', 'paid', 'dispensed', 'finished', 'cancelled']); // Untuk tracking sinkronisasi SATUSEHAT

            // Integrasi SATUSEHAT
            $table->string('satusehat_encounter_id')->nullable()->index();
            $table->text('complaint')->nullable();

            // Timestamp untuk TAT
            $table->timestamp('arrived_at')->nullable();     // Jam Daftar
            $table->timestamp('in_progress_at')->nullable(); // Jam Masuk Dokter
            $table->timestamp('finished_at')->nullable();    // Jam Selesai Dokter
            $table->timestamp('cancelled_at')->nullable();

            // Timestamp untuk internal status
            $table->timestamp('at_practitioner_at')->nullable();
            $table->timestamp('sent_to_pharmacy_at')->nullable();
            $table->timestamp('sent_for_payment_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('dispensed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outpatient_visits');
    }
};
