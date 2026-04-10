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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('satusehat_id')->nullable()->index(); 
            $table->string('nik', 16)->unique();
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            
            // Alamat Detail (Kode Wilayah BPS)
            $table->char('province_code', 2)->nullable();
            $table->char('city_code', 4)->nullable();
            $table->char('district_code', 7)->nullable();
            $table->char('village_code', 10)->nullable();
            
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
