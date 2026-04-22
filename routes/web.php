<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'clinic_active'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');
    Route::livewire('/patients', 'pages::patients.index')->name('patients.index');
    Route::livewire('/patients/register', 'pages::patients.register')->name('patients.register');
    Route::livewire('/patients/visit', 'pages::patients.visit')->name('patients.visit');

    // Rawat Jalan
    Route::livewire('/out-patients', 'pages::out-patients.index')->name('out-patients.index');
    Route::livewire('/outpatient/{visit}/diagnosis', 'pages::diagnosa.index')->name('outpatient.diagnosis');

    // Practitioner
    Route::livewire('/practitioner', 'pages::practitioner.index')->name('practitioner.index');
    Route::livewire('/practitioner/{visit}/diagnosis', 'pages::practitioner.diagnosis')->name('practitioner.diagnosis');


    // Procedure
    Route::livewire('/procedures', 'pages::procedures.index')->name('procedures.index');

    // Farmasi
    Route::livewire('/pharmacy', 'pages::pharmacy.index')->name('pharmacy.index');
    Route::livewire('/pharmacy/dispensing', 'pages::pharmacy.dispensing')->name('pharmacy.dispensing');

    // Kasir
    Route::livewire('/cashier', 'pages::cashier.index')->name('cashier.index');
    Route::livewire('/cashier/rekap', 'pages::cashier.rekap')->name('cashier.rekap');

    // Settings
    Route::livewire('/settings/master-obat', 'pages::settings.master-obat')->name('settings.master-obat');
    Route::livewire('/settings/master-clinics', 'pages::settings.clinics')->name('settings.master-clinics');
    // Route::livewire('/settings/reset-database', 'pages::settings.reset-database')->name('settings.reset-database');
});

require __DIR__ . '/settings.php';
