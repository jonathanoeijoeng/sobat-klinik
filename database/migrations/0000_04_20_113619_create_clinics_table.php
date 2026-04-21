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
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('slug')->unique(); // Untuk URL atau identitas unik
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('initial', 10)->unique()->index(); // Inisial unik untuk kode resep, invoice, dll

            // KONFIGURASI SATUSEHAT (Per Klinik punya kredensial sendiri)
            $table->string('satusehat_organization_id')->nullable();
            $table->string('satusehat_client_id')->nullable();
            $table->string('satusehat_client_secret')->nullable();
            $table->string('satusehat_organization_satusehat_id')->nullable();


            $table->enum('satusehat_env', ['sandbox', 'production'])->default('sandbox');

            // SETTING LOKALISASI & FINANCE
            $table->string('currency_prefix', 10)->default('IDR');
            $table->char('thousand_separator', 1)->default(',');
            $table->char('decimal_separator', 1)->default('.');

            $table->timestamp('active_until')->default('9999-12-31');
            $table->timestamps();
            $table->softDeletes(); // Keamanan data agar tidak benar-benar hilang
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
