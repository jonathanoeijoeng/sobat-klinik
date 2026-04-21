<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MedicinesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('medicines')->delete();

        \DB::table('medicines')->insert(array(
            0 =>
            array(
                'id' => 1,
                'clinic_id' => 1,
                'kfa_code' => '93002679',
                'name' => 'Ibuprofen 100 mg/5 mL Suspensi (60 mL, UNITED FARMATIC)',
                'display_name' => 'IBUPROFEN',
                'form_type' => 'Suspensi',
                'uom' => 'Botol',
                'manufacturer' => 'UNITED FARMATIC INDONESIA',
                'fix_price' => '8999.00',
                'het_price' => '15000.00',
                'satusehat_medication_id' => '0d4852af-e5e3-42fb-bf42-3f86a4080901',
                'last_synced_at' => '2026-04-21 16:34:09',
                'created_at' => '2026-04-21 16:34:09',
                'updated_at' => '2026-04-21 16:34:09',
            ),
            1 =>
            array(
                'id' => 2,
                'clinic_id' => 1,
                'kfa_code' => '93015366',
                'name' => 'Paracetamol 500 mg Tablet (PARACETAMOL TABLET 500 MG, STRIP)',
                'display_name' => 'PARACETAMOL',
                'form_type' => 'Tablet',
                'uom' => 'Tablet',
                'manufacturer' => 'AFIFARMA',
                'fix_price' => '0.00',
                'het_price' => '50000.00',
                'satusehat_medication_id' => 'd9008cd0-123a-47e3-84c3-4ad5462994f8',
                'last_synced_at' => '2026-04-15 19:30:09',
                'created_at' => '2026-04-15 19:30:08',
                'updated_at' => '2026-04-15 19:30:09',
            ),
            2 =>
            array(
                'id' => 3,
                'clinic_id' => 1,
                'kfa_code' => '93019537',
                'name' => 'Guaifenesin 50 mg / Chlorphenamine Maleate 1 mg/5 mL Sirup (60 mL, COHISTAN EXPECTORANT, DARYA-VARIA LABORATORIA TBK)',
                'display_name' => 'COHISTAN EXPECTORANT',
                'form_type' => 'Sirup',
                'uom' => 'Botol',
                'manufacturer' => 'DARYA-VARIA LABORATORIA TBK',
                'fix_price' => '20700.00',
                'het_price' => '52000.00',
                'satusehat_medication_id' => 'bbc9c20c-314c-4ae3-b680-07751203493d',
                'last_synced_at' => '2026-04-17 19:30:30',
                'created_at' => '2026-04-17 19:30:29',
                'updated_at' => '2026-04-17 19:30:30',
            ),
            3 =>
            array(
                'id' => 4,
                'clinic_id' => 1,
                'kfa_code' => '93010079',
                'name' => 'Guaifenesin 50 mg / Bromhexin Hydrochloride 10 mg/5 mL Sirup (60 mL, SILADEX MUCOLYTIC & EXPECTORANT)',
                'display_name' => 'SILADEX MUCOLYTIC & EXPECTORANT',
                'form_type' => 'Sirup',
                'uom' => 'Botol Plastik',
                'manufacturer' => 'KONIMEX',
                'fix_price' => '0.00',
                'het_price' => '25000.00',
                'satusehat_medication_id' => '28072f00-99ed-41f0-9edd-e17fc58a99bd',
                'last_synced_at' => '2026-04-19 19:30:41',
                'created_at' => '2026-04-19 19:30:41',
                'updated_at' => '2026-04-19 19:30:41',
            ),
            4 =>
            array(
                'id' => 5,
                'clinic_id' => 1,
                'kfa_code' => '93006387',
                'name' => 'Amoxicillin Trihydrate 125 mg/5 mL Sirup Kering (60 mL, PHARMA LABORATORIES)',
                'display_name' => 'AMOXICILLIN',
                'form_type' => 'Sirup Kering',
                'uom' => 'Botol',
                'manufacturer' => 'PHARMA LABORATORIES',
                'fix_price' => '4995.00',
                'het_price' => '55000.00',
                'satusehat_medication_id' => 'f1256843-a24e-4e89-9389-e5000b65dd6e',
                'last_synced_at' => '2026-04-20 19:31:16',
                'created_at' => '2026-04-20 19:31:15',
                'updated_at' => '2026-04-20 19:31:16',
            ),
            5 =>
            array(
                'id' => 6,
                'clinic_id' => 1,
                'kfa_code' => '93001812',
                'name' => 'Diclofenac Sodium 25 mg Tablet Salut Enterik (VOLTAREN)',
                'display_name' => 'VOLTAREN',
                'form_type' => 'Tablet Salut Enterik',
                'uom' => 'Tablet',
                'manufacturer' => 'NOVARTIS INDONESIA',
                'fix_price' => '5377.00',
                'het_price' => '51000.00',
                'satusehat_medication_id' => 'a00fd243-0627-42fe-a42d-744941f40fc7',
                'last_synced_at' => '2026-03-21 19:31:54',
                'created_at' => '2026-03-21 19:31:53',
                'updated_at' => '2026-03-21 19:31:54',
            ),
            6 =>
            array(
                'id' => 7,
                'clinic_id' => 1,
                'kfa_code' => '93012160',
                'name' => 'Diclofenac Diethylamine 11,6 mg Emulgel (5 G, VOLTAREN)',
                'display_name' => 'VOLTAREN',
                'form_type' => 'Gel',
                'uom' => 'Tube',
                'manufacturer' => 'STERLING PRODUCTS INDONESIA',
                'fix_price' => '32.42',
                'het_price' => '12000.00',
                'satusehat_medication_id' => '7bbf7e10-cfbc-48f2-b650-bd3558965d18',
                'last_synced_at' => '2026-02-21 19:32:04',
                'created_at' => '2026-02-21 19:32:03',
                'updated_at' => '2026-02-21 19:32:04',
            ),
            7 =>
            array(
                'id' => 8,
                'clinic_id' => 1,
                'kfa_code' => '93001854',
                'name' => 'Diclofenac Sodium 50 mg Tablet Dispersibel (CATAFLAM D)',
                'display_name' => 'CATAFLAM D',
                'form_type' => 'Tablet Dispersibel',
                'uom' => 'Tablet',
                'manufacturer' => 'NOVARTIS INDONESIA',
                'fix_price' => '8507.00',
                'het_price' => '10000.00',
                'satusehat_medication_id' => '9a6b04ed-a046-484a-a0b9-22d19d0ed99e',
                'last_synced_at' => '2026-04-19 19:32:25',
                'created_at' => '2026-04-19 19:32:24',
                'updated_at' => '2026-04-19 19:32:25',
            ),
            8 =>
            array(
                'id' => 9,
                'clinic_id' => 1,
                'kfa_code' => '93001844',
                'name' => 'Diclofenac Potassium 50 mg Tablet Salut Gula (CATAFLAM)',
                'display_name' => 'CATAFLAM',
                'form_type' => 'Tablet Salut Gula',
                'uom' => 'Tablet',
                'manufacturer' => 'NOVARTIS INDONESIA',
                'fix_price' => '8725.90',
                'het_price' => '15000.00',
                'satusehat_medication_id' => '8d671c10-6973-49a2-89cc-5ddf1b7be890',
                'last_synced_at' => '2026-04-21 19:32:34',
                'created_at' => '2026-04-21 19:32:33',
                'updated_at' => '2026-04-21 19:32:34',
            ),
        ));
    }
}
