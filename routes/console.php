<?php

use Database\Seeders\OutpatientVisitSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('outpatient-visits:refresh-seed', function () {
    Schema::disableForeignKeyConstraints();

    DB::table('prescriptions')->truncate();
    DB::table('invoices')->truncate();
    DB::table('vital_signs')->truncate();
    DB::table('out_patient_diagnoses')->truncate();
    DB::table('outpatient_visits')->truncate();

    Schema::enableForeignKeyConstraints();

    $this->call('db:seed', [
        '--class' => OutpatientVisitSeeder::class,
        '--force' => true,
    ]);

    $this->info('Outpatient visits refreshed and seeded.');
})->purpose('Refresh outpatient visit data and run the outpatient visit seeder');

Schedule::command('outpatient-visits:refresh-seed')
    ->sundays()
    ->at('00:00')
    ->timezone(config('app.timezone', 'Asia/Jakarta'))
    ->withoutOverlapping();
