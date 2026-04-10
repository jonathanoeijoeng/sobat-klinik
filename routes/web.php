<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/patients', 'pages::patients.index')->name('patients.index');
    Route::livewire('/patients/register', 'pages::patients.register')->name('patients.register');
    Route::livewire('/patients/visit', 'pages::patients.visit')->name('patients.visit');
});

require __DIR__ . '/settings.php';
