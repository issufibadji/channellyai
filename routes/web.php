<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified', 'check2fa'])
    ->name('dashboard');

require __DIR__.'/auth.php';
