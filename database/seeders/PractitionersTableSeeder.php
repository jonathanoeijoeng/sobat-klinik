<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PractitionersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('practitioners')->delete();

        \DB::table('practitioners')->insert(array(
            0 =>
            array(
                'id' => 1,
                'clinic_id' => 1,
                'nik' => '3322071302900002',
                'name' => 'dr. Yoga Yandika, Sp.A',
                'satusehat_id' => '10006926841',
                'ihs_number' => '1234567890',
                'sip' => '1234567887654322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '250000.00',
                'created_at' => '2026-01-10 16:29:05',
                'updated_at' => '2026-01-10 16:29:05',
            ),
            1 =>
            array(
                'id' => 2,
                'clinic_id' => 1,
                'nik' => '7209061211900001',
                'name' => 'dr. Alexander',
                'satusehat_id' => '10009880728',
                'ihs_number' => '',
                'sip' => '123456788764322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '200000.00',
                'created_at' => '2026-01-11 16:29:05',
                'updated_at' => '2026-01-11 16:29:05',
            ),
            2 =>
            array(
                'id' => 3,
                'clinic_id' => 1,
                'nik' => '3171071609900003',
                'name' => 'dr. Syarifuddin, Sp.Pd.',
                'satusehat_id' => '10001354453',
                'ihs_number' => '',
                'sip' => '1234537887654322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '225000.00',
                'created_at' => '2026-02-11 16:29:05',
                'updated_at' => '2026-02-11 16:29:05',
            ),
            3 =>
            array(
                'id' => 4,
                'clinic_id' => 1,
                'nik' => '3207192310600004',
                'name' => 'dr. Nicholas Evan, Sp.B.',
                'satusehat_id' => '10010910332',
                'ihs_number' => '',
                'sip' => '1234237887654322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '225000.00',
                'created_at' => '2026-02-21 16:29:05',
                'updated_at' => '2026-02-21 16:29:05',
            ),
            4 =>
            array(
                'id' => 5,
                'clinic_id' => 1,
                'nik' => '3217040109800006',
                'name' => 'dr. Olivia Kirana, Sp.OG',
                'satusehat_id' => '10002074224',
                'ihs_number' => '',
                'sip' => '1234537887654342',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '225000.00',
                'created_at' => '2026-02-13 16:29:05',
                'updated_at' => '2026-02-13 16:29:05',
            ),
            5 =>
            array(
                'id' => 6,
                'clinic_id' => 1,
                'nik' => '6408130207800005',
                'name' => 'dr. Dito Arifin, Sp.M.',
                'satusehat_id' => '10018180913',
                'ihs_number' => '',
                'sip' => '1234537887652322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '200000.00',
                'created_at' => '2026-04-10 16:29:05',
                'updated_at' => '2026-04-10 16:29:05',
            ),
            6 =>
            array(
                'id' => 7,
                'clinic_id' => 1,
                'nik' => '3519111703800007',
                'name' => 'dr. Alicia Chrissy, Sp.N.',
                'satusehat_id' => '10012572188',
                'ihs_number' => '',
                'sip' => '1234537787654322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '175000.00',
                'created_at' => '2026-03-11 16:29:05',
                'updated_at' => '2026-03-11 16:29:05',
            ),
            7 =>
            array(
                'id' => 8,
                'clinic_id' => 1,
                'nik' => '5271002009700008',
                'name' => 'dr. Nathalie Tan, Sp.PK.',
                'satusehat_id' => '10018452434',
                'ihs_number' => '',
                'sip' => '1234537887654312',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '200000.00',
                'created_at' => '2026-04-12 16:29:05',
                'updated_at' => '2026-04-12 16:29:05',
            ),
            8 =>
            array(
                'id' => 9,
                'clinic_id' => 1,
                'nik' => '3313096403900009',
                'name' => 'Sheila Annisa S.Kep',
                'satusehat_id' => '10014058550',
                'ihs_number' => '',
                'sip' => '1234537886654322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '215000.00',
                'created_at' => '2026-03-21 16:29:05',
                'updated_at' => '2026-03-21 16:29:05',
            ),
            9 =>
            array(
                'id' => 10,
                'clinic_id' => 1,
                'nik' => '3578083008700010',
                'name' => 'apt. Aditya Pradhana, S.Farm.',
                'satusehat_id' => '10001915884',
                'ihs_number' => '',
                'sip' => '1234537787654322',
                'profession_type' => 'doctor',
                'is_active' => true,
                'fee' => '225000.00',
                'created_at' => '2026-03-12 16:29:05',
                'updated_at' => '2026-03-12 16:29:05',
            ),
        ));
    }
}
