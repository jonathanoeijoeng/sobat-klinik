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
        Schema::create('icd10s', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Contoh: A00.0
            $table->string('name_en');        // Nama internasional
            $table->string('name_id')->nullable(); // Nama Indonesia (jika ada)
            $table->boolean('is_active')->default(true);
            $table->string('version')->nullable(); // Versi ICD-10
            $table->index(['code', 'name_id']); // Index penting untuk search
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icd10s');
    }
};
