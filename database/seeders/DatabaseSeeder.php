<?php

namespace Database\Seeders;

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
        User::create([
            'name' => 'Jonathan',
            'email' => 'jonathan.oeijoeng@gmail.com',
        ]);

        $this->call([
            OrganizationSeeder::class,
            PatientSeeder::class,
            PractitionerSeeder::class,
            LocationSeeder::class,
            Icd10sSeeder::class,
        ]);
    }
}
