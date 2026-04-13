<?php

namespace Database\Seeders;

use App\Models\Icd10;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class Icd10sSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lokasi file CSV
        $file = storage_path('app/public/csv/ICD10.csv');
        
        // Buka file
        $handle = fopen($file, 'r');

        // Lewati baris pertama (header) jika ada
        $header = fgetcsv($handle, 1000, ',');

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            Icd10::create([
                'code'  => $data[0],
                'name_en' => $data[1],
                'is_active' => true,
                'version' => $data[2],
            ]);
        }

        fclose($handle);
    }
}
