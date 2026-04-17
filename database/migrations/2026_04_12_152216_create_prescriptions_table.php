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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outpatient_visit_id')->constrained()->cascadeOnDelete();

            // --- TAMBAHKAN INI ---
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->string('medicine_name'); // Denormalisasi: simpan nama obat saat resep dibuat
            $table->string('instruction')->nullable(); // Contoh: "3 x 1 sesudah makan"
            $table->integer('qty_ordered')->default(0);
            $table->integer('qty_dispensed')->default(0);
            $table->string('uom')->default('Pcs'); // Satuan: Tablet, Botol, dll
            // ---------------------

            // Status Farmasi
            $table->enum('status', ['draft', 'sent_to_pharmacy', 'sent_for_payment', 'paid', 'ready', 'dispensed', 'external'])->default('draft');

            // Integrasi SATUSEHAT
            $table->string('satusehat_medication_request_id')->nullable();
            $table->string('satusehat_medication_dispense_id')->nullable();

            // Timestamp untuk TAT Farmasi (Turn Around Time)
            $table->timestamp('sent_to_pharmacy_at')->nullable();
            $table->timestamp('sent_for_payment_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamp('external_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
