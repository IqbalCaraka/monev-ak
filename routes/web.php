<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\InstansiController;
use App\Http\Controllers\AktivitasPegawaiController;
use App\Http\Controllers\StagingLogController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Pengaturan Routes
Route::prefix('pengaturan')->group(function () {
    Route::resource('pegawai', PegawaiController::class);
    Route::post('pegawai/{pegawai}/toggle-active', [PegawaiController::class, 'toggleActive'])->name('pegawai.toggleActive');

    Route::resource('instansi', InstansiController::class);
});

// Statistik Routes
Route::prefix('statistik')->group(function () {
    Route::get('aktivitas-pegawai', [AktivitasPegawaiController::class, 'index'])->name('aktivitas-pegawai.index');
    Route::post('aktivitas-pegawai/upload', [AktivitasPegawaiController::class, 'uploadCsv'])->name('aktivitas-pegawai.upload');
    Route::get('aktivitas-pegawai/{nip}', [AktivitasPegawaiController::class, 'show'])->name('aktivitas-pegawai.show');
    Route::get('aktivitas-pegawai/{nip}/{kategori}', [AktivitasPegawaiController::class, 'detailKategori'])->name('aktivitas-pegawai.detail-kategori');

    // Staging logs routes
    Route::get('staging', [StagingLogController::class, 'index'])->name('staging.index');
    Route::get('staging/{nip}', [StagingLogController::class, 'show'])->name('staging.show');
    Route::post('staging/{nip}/process', [StagingLogController::class, 'process'])->name('staging.process');
});
