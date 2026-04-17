<?php

namespace Database\Seeders;

use App\Models\Practitioner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PractitionerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Practitioner::create([
            'nik' => '3322071302900002',
            'name' => 'dr. Yoga Yandika, Sp.A',
            'satusehat_id' => '10006926841',
            'ihs_number' => '1234567890',
            'sip' => '1234567887654322',
            'profession_type' => 'doctor',
            'is_active' => true,
            'fee' => 250000,
        ]);
    }
}
