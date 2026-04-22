<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Icd10;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\OutPatientDiagnosis;
use App\Models\OutpatientVisit;
use App\Models\Practitioner;
use App\Models\Prescription;
use App\Models\User;
use App\Models\VitalSign;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            ClinicSeeder::class,
            OrganizationSeeder::class,
            PatientSeeder::class,
            LocationSeeder::class,
            Icd10sSeeder::class,
            MedicinesTableSeeder::class,
            PractitionersTableSeeder::class,
        ]);

        User::create([
            'name' => 'Jonathan',
            'email' => 'jonathan.oeijoeng@gmail.com',
            'password' => bcrypt('password'),
            'clinic_id' => 1
        ]);

        $initial = Clinic::find(1)->initial;
        $jumlahData = Cache::store('file')->get('seeder_jumlah_data', 1267);
        $rentangHari = Cache::store('file')->get('seeder_rentang_hari', 53);
    
        for ($i = 0; $i < $jumlahData; $i++) {

            // 1. Tentukan Waktu Kedatangan (Arrived)
            $baseTime = Carbon::now()->subDays(rand(1, $rentangHari))->setTime(rand(8, 18), rand(0, 59));

            $visitNumber = $initial . '-' . $baseTime->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $invoiceNumber = 'INV-' . $baseTime->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);

            // Simulasi Alur Waktu (TAT)
            $arrivedAt = $baseTime;
            $inProgressAt = $arrivedAt->copy()->addMinutes(rand(10, 30)); // Tunggu 10-30 menit
            $paidAt = $arrivedAt->copy()->addMinutes(rand(5, 15));        // Bayar 5-15 menit kemudian
            $finishedAt = $paidAt->copy()->addMinutes(rand(15, 45)); // Periksa 15-45 menit

            $icd10Stock = Icd10::inRandomOrder()->limit(50)->get();

            $doctorId = rand(1, 10);
            $practitioner = Practitioner::find($doctorId);

            $regFee = 50000; // Contoh Biaya Pendaftaran Tetap
            $practitionerFee = $practitioner->fee ?? 50000;

            $totalMedicinePrice = 0;

            // 2. Buat Outpatient Visit
            $visit = OutpatientVisit::create([
                'clinic_id'       => 1, // Asumsi id klinik 1
                'visit_number'    => $visitNumber,
                'patient_id'      => rand(1, 10),
                'practitioner_id' => $doctorId,
                'location_id'     => rand(1, 3), // ID Poli
                'status'          => 'finished',
                'internal_status' => 'finished',
                'satusehat_encounter_id' => (string) Str::uuid(),
                'complaint'       => 'Keluhan umum pasien ke-' . ($i + 1),

                // Timestamp TAT
                'arrived_at'      => $arrivedAt,
                'in_progress_at'  => $inProgressAt,
                'finished_at'     => $finishedAt,
                'at_practitioner_at' => $inProgressAt,
                'sent_for_payment_at' => $finishedAt,
                'paid_at'         => $paidAt,
                'dispensed_at'    => $paidAt->copy()->addMinutes(5),

                'created_at'      => $arrivedAt,
                'updated_at'      => $paidAt,
            ]);

            // 3. Buat Vital Sign
            VitalSign::create([
                'clinic_id' => 1,
                'outpatient_visit_id'    => $visit->id,
                'systole'     => rand(110, 130),
                'diastole'    => rand(70, 90),
                'temperature' => rand(36, 37) . '.' . rand(1, 9),
                'weight'      => rand(50, 70),
                'height'      => rand(150, 170),
                'satusehat_observation_blood_pressure_id' => (string) Str::uuid(),
                'satusehat_observation_temperature_id' => (string) Str::uuid(),
                'satusehat_observation_weight_id' => (string) Str::uuid(),
                'satusehat_observation_height_id' => (string) Str::uuid(),
                'created_at'  => $arrivedAt,
                'updated_at'  => $arrivedAt,
            ]);

            // 4. Buat Diagnosa
            $diagCount = rand(1, 3);

            for ($j = 0; $j < $diagCount; $j++) {
                $randomIcd = $icd10Stock->random();

                OutPatientDiagnosis::create([
                    'clinic_id'           => $visit->clinic_id,
                    'outpatient_visit_id' => $visit->id,
                    'icd10_code'          => $randomIcd->code,
                    'icd10_display'       => $randomIcd->name_en,
                    // Diagnosa pertama ($j == 0) selalu jadi Primary
                    'is_primary'          => ($j === 0),
                    'satusehat_condition_id' => (string) \Illuminate\Support\Str::uuid(),
                    'created_at'          => $visit->in_progress_at,
                    'updated_at'          => $visit->in_progress_at,
                ]);
            }

            // 5. Buat Resep & Hitung Total Harga Obat
            $numObat = rand(1, 3);
            for ($j = 0; $j < $numObat; $j++) {
                // Ambil obat random dari master (ID 1-10)
                $medicine = \App\Models\Medicine::inRandomOrder()->first();
                $qty = rand(1, 15);

                \App\Models\Prescription::create([
                    'clinic_id'           => $visit->clinic_id,
                    'outpatient_visit_id' => $visit->id,
                    'medicine_id'         => $medicine->id,
                    'medicine_name'       => $medicine->name, // Denormalisasi
                    'instruction'         => collect(['3 x 1 sesudah makan', '2 x 1 sebelum makan', '1 x 1 malam hari'])->random(),
                    'qty_ordered'         => $qty,
                    'qty_dispensed'       => $qty, // Langsung dianggap terpenuhi semua
                    'uom'                 => $medicine->uom ?? 'Tablet',

                    // Status Farmasi
                    'status'              => 'dispensed', // Selesai diserahkan

                    // Integrasi SATUSEHAT
                    'satusehat_medication_request_id' => (string) \Illuminate\Support\Str::uuid(),
                    'satusehat_medication_dispense_id' => (string) \Illuminate\Support\Str::uuid(),

                    // Timestamp TAT Farmasi (Sinkron dengan Visit)
                    'sent_to_pharmacy_at' => $visit->sent_to_pharmacy_at, // Jam dokter klik kirim
                    'sent_for_payment_at' => $visit->sent_for_payment_at, // Jam admin kirim tagihan
                    'paid_at'             => $visit->paid_at,             // Jam lunas
                    'dispensed_at'        => $visit->dispensed_at,        // Jam obat diserahkan ke pasien

                    'created_at'          => $visit->finished_at,
                    'updated_at'          => $visit->dispensed_at,
                ]);

                // Kalkulasi untuk Invoice (Menggunakan het_price seperti request sebelumnya)
                $totalMedicinePrice += ($medicine->het_price ?? 0) * $qty;
            }

            // 6. Buat Invoice (Fee Dokter + Total Obat)

            Invoice::create([
                'clinic_id'           => 1,
                'outpatient_visit_id' => $visit->id,
                'invoice_number'      => $invoiceNumber,
                'registration_fee'    => $regFee,
                'practitioner_fee'    => $practitionerFee,
                'medicine_total'      => $totalMedicinePrice,
                'grand_total'         => $regFee + $practitionerFee + $totalMedicinePrice,
                'payment_status'      => 'paid',
                'payment_method'      => collect(['cash', 'qris', 'transfer'])->random(),
                'paid_at'             => $paidAt,
                'created_at'          => $paidAt,
            ]);
        }
    }
}
