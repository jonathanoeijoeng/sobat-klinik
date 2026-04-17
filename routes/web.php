<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');
    Route::livewire('/patients', 'pages::patients.index')->name('patients.index');
    Route::livewire('/patients/register', 'pages::patients.register')->name('patients.register');
    Route::livewire('/patients/visit', 'pages::patients.visit')->name('patients.visit');

    // Rawat Jalan
    Route::livewire('/out-patients', 'pages::out-patients.index')->name('out-patients.index');
    Route::livewire('/outpatient/{visit}/diagnosis', 'pages::diagnosa.index')->name('outpatient.diagnosis');

    // Procedure
    Route::livewire('/procedures', 'pages::procedures.index')->name('procedures.index');

    // Farmasi
    Route::livewire('/pharmacy', 'pages::pharmacy.index')->name('pharmacy.index');
    Route::livewire('/pharmacy/dispensing', 'pages::pharmacy.dispensing')->name('pharmacy.dispensing');

    // Kasir
    Route::livewire('/cashier', 'pages::cashier.index')->name('cashier.index');

    // Settings
    Route::livewire('/settings/master-obat', 'pages::settings.master-obat')->name('settings.master-obat');
});

require __DIR__ . '/settings.php';
