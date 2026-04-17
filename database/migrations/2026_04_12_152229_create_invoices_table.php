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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('outpatient_visits')->cascadeOnDelete();
            $table->string('invoice_number')->unique(); // INV-YYYYMMDD-XXXX

            // Komponen Biaya (IDR)
            $table->decimal('registration_fee', 15, 2)->default(0);
            $table->decimal('practitioner_fee', 15, 2)->default(0);
            $table->decimal('medicine_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            // Status & Pembayaran
            $table->enum('payment_status', ['unpaid', 'paid', 'expired', 'refunded'])->default('unpaid');
            $table->string('payment_method')->nullable(); // 'cash' atau 'xendit'
            $table->string('xendit_external_id')->nullable()->index();
            $table->string('xendit_invoice_url')->nullable();

            $table->timestamp('paid_at')->nullable(); // Jam bayar (manual/webhook)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
