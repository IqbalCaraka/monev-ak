<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\InstansiController;
use App\Http\Controllers\PicController;
use App\Http\Controllers\AktivitasPegawaiController;
use App\Http\Controllers\StagingLogController;
use App\Http\Controllers\PerhitunganSkorArsipController;
use App\Http\Controllers\DmsController;
use App\Http\Controllers\MonevDmsController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard-dms', [DashboardController::class, 'dms'])->name('dashboard.dms');
Route::get('/dashboard-dms/filter-data', [DashboardController::class, 'filterMonevData'])->name('dashboard.monev.filter');
Route::get('/dashboard-dms/export-monev-pdf', [DashboardController::class, 'exportMonevPdf'])->name('dashboard.monev.export-pdf');
Route::get('/dashboard-dms/compare-periods', [DashboardController::class, 'comparePeriods'])->name('dashboard.monev.compare');
Route::get('/dashboard-dms/export-kanreg-excel', [DashboardController::class, 'exportKanregExcel'])->name('dashboard.monev.export-kanreg-excel');
Route::get('/dashboard-dms/export-kanreg-pdf', [DashboardController::class, 'exportKanregPdf'])->name('dashboard.monev.export-kanreg-pdf');
Route::get('/dashboard-dms/export-all-excel', [DashboardController::class, 'exportAllInstansiExcel'])->name('dashboard.monev.export-all-excel');
Route::get('/dashboard-dms/export-all-pdf', [DashboardController::class, 'exportAllInstansiPdf'])->name('dashboard.monev.export-all-pdf');
Route::get('/dashboard-dms/export-kanreg-summary-excel', [DashboardController::class, 'exportKanregSummaryExcel'])->name('dashboard.monev.export-kanreg-summary-excel');
Route::get('/dashboard-dms/export-kanreg-summary-pdf', [DashboardController::class, 'exportKanregSummaryPdf'])->name('dashboard.monev.export-kanreg-summary-pdf');
Route::get('/dashboard-dms/export-comparison-excel', [DashboardController::class, 'exportComparisonExcel'])->name('dashboard.monev.export-comparison-excel');
Route::get('/dashboard-dms/export-comparison-pdf', [DashboardController::class, 'exportComparisonPdf'])->name('dashboard.monev.export-comparison-pdf');
Route::get('/dashboard-dms/export-comparison-kanreg-excel', [DashboardController::class, 'exportComparisonKanregExcel'])->name('dashboard.monev.export-comparison-kanreg-excel');
Route::get('/dashboard-dms/export-comparison-kanreg-pdf', [DashboardController::class, 'exportComparisonKanregPdf'])->name('dashboard.monev.export-comparison-kanreg-pdf');

// Pengaturan Routes
Route::prefix('pengaturan')->group(function () {
    Route::resource('pegawai', PegawaiController::class);
    Route::post('pegawai/{pegawai}/toggle-active', [PegawaiController::class, 'toggleActive'])->name('pegawai.toggleActive');

    Route::resource('instansi', InstansiController::class);

    Route::resource('pic', PicController::class);
    Route::post('pic/{pic}/toggle-active', [PicController::class, 'toggleActive'])->name('pic.toggleActive');
    Route::get('pic/{pic}/export-pdf', [PicController::class, 'exportPdf'])->name('pic.exportPdf');
});

// Statistik Routes
Route::prefix('statistik')->group(function () {
    Route::get('aktivitas-pegawai', [AktivitasPegawaiController::class, 'index'])->name('aktivitas-pegawai.index');
    Route::get('aktivitas-pegawai/export-pdf', [AktivitasPegawaiController::class, 'exportPdf'])->name('aktivitas-pegawai.export-pdf');
    Route::get('aktivitas-pegawai/export-pic-pdf', [AktivitasPegawaiController::class, 'exportPicPdf'])->name('aktivitas-pegawai.export-pic-pdf');
    Route::post('aktivitas-pegawai/upload', [AktivitasPegawaiController::class, 'uploadCsv'])->name('aktivitas-pegawai.upload');
    Route::get('aktivitas-pegawai/{nip}', [AktivitasPegawaiController::class, 'show'])->name('aktivitas-pegawai.show');
    Route::get('aktivitas-pegawai/{nip}/{kategori}', [AktivitasPegawaiController::class, 'detailKategori'])->name('aktivitas-pegawai.detail-kategori');

    // Staging logs routes
    Route::get('staging', [StagingLogController::class, 'index'])->name('staging.index');
    Route::get('staging/{nip}', [StagingLogController::class, 'show'])->name('staging.show');
    Route::post('staging/{nip}/process', [StagingLogController::class, 'process'])->name('staging.process');
});

// Perhitungan Skor Arsip Routes
Route::prefix('skor-arsip')->group(function () {
    Route::get('/', [PerhitunganSkorArsipController::class, 'index'])->name('skor-arsip.index');
    Route::post('/process', [PerhitunganSkorArsipController::class, 'process'])->name('skor-arsip.process');
});

// Ubah Format - CSV to Excel converter
Route::post('/ubah-format/process', [\App\Http\Controllers\UbahFormatController::class, 'processUpload'])->name('ubah-format.process');

// DMS Routes
Route::prefix('dms')->group(function () {
    Route::post('/upload', [DmsController::class, 'upload'])->name('dms.upload');
    Route::get('/instansi', [DmsController::class, 'allInstansi'])->name('dms.instansi.all');
    Route::get('/instansi/{instansiId}/detail', [DmsController::class, 'instansiDetailFull'])->name('dms.instansi.detail-full');
    Route::get('/{uploadId}', [DmsController::class, 'show'])->name('dms.show');
    Route::get('/{uploadId}/progress', [DmsController::class, 'progress'])->name('dms.progress');
    Route::post('/calculate-instansi', [DmsController::class, 'calculateInstansi'])->name('dms.calculate-instansi');
    Route::post('/{uploadId}/calculate-all', [DmsController::class, 'calculateAll'])->name('dms.calculate-all');
    Route::get('/{uploadId}/instansi/{instansiId}', [DmsController::class, 'instansiDetail'])->name('dms.instansi-detail');
});

// Monev DMS Routes
Route::prefix('monev-dms')->group(function () {
    Route::post('/upload-csv', [MonevDmsController::class, 'uploadMonevCsv'])->name('monev-dms.upload-csv');
    Route::post('/delete', [MonevDmsController::class, 'deleteMonevData'])->name('monev-dms.delete');
});

// Monev DMS API Routes
Route::prefix('api/monev-dms')->group(function () {
    Route::get('/search-instansi', [\App\Http\Controllers\Api\MonevDmsApiController::class, 'searchInstansi'])->name('api.monev-dms.search');
    Route::get('/search-aktivitas-pegawai', [\App\Http\Controllers\Api\MonevDmsApiController::class, 'searchAktivitasPegawai'])->name('api.aktivitas-pegawai.search');
    Route::get('/pic-stats', [\App\Http\Controllers\Api\MonevDmsApiController::class, 'getPicStats'])->name('api.aktivitas-pegawai.pic-stats');
    Route::get('/mapping-dokumen', [\App\Http\Controllers\Api\MonevDmsApiController::class, 'getMappingDokumen'])->name('api.aktivitas-pegawai.mapping-dokumen');
    Route::get('/inject-dokumen', [\App\Http\Controllers\Api\MonevDmsApiController::class, 'getInjectDokumen'])->name('api.aktivitas-pegawai.inject-dokumen');
});
