<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create([
            'clinic_id' => 1,
            'name' => 'Ruang 1A IRJT',
            'satusehat_id' => '1e3f7c41-0812-44e5-b746-bbf3fea3c13a',
        ]);
    }
}
