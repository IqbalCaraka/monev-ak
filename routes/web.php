<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PegawaiController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Pengaturan Routes
Route::prefix('pengaturan')->group(function () {
    Route::resource('pegawai', PegawaiController::class);
    Route::post('pegawai/{pegawai}/toggle-active', [PegawaiController::class, 'toggleActive'])->name('pegawai.toggleActive');
});
