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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->index()->constrained('clinics');
            $table->string('kfa_code')->unique()->index(); // Kode dari KFA
            $table->string('name');
            $table->string('display_name');
            $table->string('form_type')->nullable(); // Tablet, Sirup, dll
            $table->string('uom'); // Satuan: Tablet, Botol, dll
            $table->string('manufacturer')->nullable();
            $table->decimal('fix_price', 15, 2)->default(0);
            $table->decimal('het_price', 15, 2)->default(5000);
            $table->string('satusehat_medication_id')->nullable()->index(); // ID dari SatuSehat
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
