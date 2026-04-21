<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat pasien yang ada di resource satusehat
        $patients = [
            [
                'satusehat_patient_id' => 'P02280547535',
                'clinic_id' => 1,
                'nik' => '9104025209000006',
                'medical_record_number' => '2603180101',
                'name' => 'Salsabilla Anjani Rizki',
                'gender' => 'female',
                'birth_date' => '2001-04-16',
                'phone_number' => '1234567890',
                'address' => 'Bandung',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P02478375538',
                'clinic_id' => 1,
                'nik' => '9271060312000001',
                'medical_record_number' => '2604150111',
                'name' => 'Ardianto Putra',
                'gender' => 'male',
                'birth_date' => '1992-01-09',
                'phone_number' => '1234567891',
                'address' => 'Bandung',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P03647103112',
                'clinic_id' => 1,
                'nik' => '9204014804000002',
                'medical_record_number' => '2605120121',
                'name' => 'Claudia Sintia',
                'gender' => 'female',
                'birth_date' => '1989-11-03',
                'phone_number' => '1234567892',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P00805884304',
                'clinic_id' => 1,
                'nik' => '9104224509000003',
                'medical_record_number' => '2602190131',
                'name' => 'Elizabeth Dior',
                'gender' => 'female',
                'birth_date' => '1976-07-07',
                'phone_number' => '1234567893',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P00912894463',
                'clinic_id' => 1,
                'nik' => '9104223107000004',
                'medical_record_number' => '2602190141',
                'name' => 'Dr. Alan Bagus Prasetya',
                'gender' => 'male',
                'birth_date' => '1977-09-03',
                'phone_number' => '1234567894',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P01654557057',
                'clinic_id' => 1,
                'nik' => '9104224606000005',
                'medical_record_number' => '2601190151',
                'name' => 'Ghina Assyifa',
                'gender' => 'female',
                'birth_date' => '2004-08-21',
                'phone_number' => '1234567895',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P01836748436',
                'clinic_id' => 1,
                'nik' => '9201076001000007',
                'medical_record_number' => '2603190161',
                'name' => 'Theodore Elisjah',
                'gender' => 'female',
                'birth_date' => '1985-09-18',
                'phone_number' => '1234567896',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P00883356749',
                'clinic_id' => 1,
                'nik' => '9201394901000008',
                'medical_record_number' => '2603190171',
                'name' => 'Sonia Herdianti',
                'gender' => 'female',
                'birth_date' => '1996-06-08',
                'phone_number' => '1234567897',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P01058967035',
                'clinic_id' => 1,
                'nik' => '9201076407000009',
                'medical_record_number' => '2601190181',
                'name' => 'Nancy Wang',
                'gender' => 'female',
                'birth_date' => '1955-10-10',
                'phone_number' => '1234567898',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ],
            [
                'satusehat_patient_id' => 'P02428473601',
                'clinic_id' => 1,
                'nik' => '9210060207000010',
                'medical_record_number' => '2601150191',
                'name' => 'Syarif Muhammad',
                'gender' => 'male',
                'birth_date' => '1988-11-02',
                'phone_number' => '1234567899',
                'address' => 'Jakarta',
                'last_sync_at' => now(),
            ]
        ];

        foreach ($patients as $patient) {
            Patient::create($patient);
        }
    }
}
