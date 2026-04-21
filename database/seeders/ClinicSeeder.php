<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clinics = [
            [
                'name' => 'Klinik Sehat Selalu',
                'slug' => 'klinik-sehat-selalu',
                'address' => 'Jl. Sehat No. 123, Jakarta',
                'phone' => '021-12345678',
                'email' => '1qNt2@example.com',
                'initial' => 'KSS',
                'satusehat_organization_id' => '62216c8e-c944-4810-8bea-b94361c6058c',
                'satusehat_client_id' => 'Z5h2Loox3tlg5GQknPtIQdGHGrBI9t84x8qv9jXxcy7ZIdS5',
                'satusehat_client_secret' => 'BzZQuO43pJ2Mg23fg8SGXJrbAdO4xmjHiAb0zY8tFAcny6Lm1BbgcTAk9nCJGL2H',
                'logo' => 'E20srgpMFgevuPr9ew1kZV4aSZUpwuphguZwXHGz.png',
            ],
            [
                'name' => 'Klinik Bagus',
                'slug' => 'klinik-bagus',
                'address' => 'Jl. Waras No. 12, Bandung',
                'phone' => '021-12345678',
                'email' => '1qNt2@example.com',
                'initial' => 'KB',
                'satusehat_organization_id' => '25216c8e-c944-4810-8bea-b94361c6058c',
                'satusehat_client_id' => 'Y2h2Loox3tlg5GQknPtIQdGHGrBI9t84x8qv9jXxcy7ZIdS5',
                'satusehat_client_secret' => 'EySQuO43pJ2Mg23fg8SGXJrbAdO4xmjHiAb0zY8tFAcny6Lm1BbgcTAk9nCJGL2H',
                'logo' => 'UI9hgGFiI3WIk97vvg5jhy0BYfsOZsQxJtIJuwaP.png',
            ],
        ];

        foreach ($clinics as $clinic) {
            Clinic::create($clinic);
        };
    }
}
