<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            PractitionerSeeder::class,
            LocationSeeder::class,
            Icd10sSeeder::class,
        ]);

        User::create([
            'name' => 'Jonathan',
            'email' => 'jonathan.oeijoeng@gmail.com',
            'password' => bcrypt('password'),
            'clinic_id' => 1
        ]);
    }
}
