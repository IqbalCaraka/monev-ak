@extends('layouts.app')

@section('title', 'Statistik Aktivitas Pegawai')

@section('content')

@php
    // Temporary empty collections for sections that will be loaded via AJAX
    $mappingDokumen = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
    $injectDokumen = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
    $picStats = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Date Filter Form -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('aktivitas-pegawai.index') }}" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-1 small"><i class="ti-calendar me-1"></i> Dari Tanggal</label>
                            <input type="date"
                                   name="date_from"
                                   class="form-control"
                                   value="{{ $dateFrom ?? '' }}"
                                   max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1 small"><i class="ti-calendar me-1"></i> Sampai Tanggal</label>
                            <input type="date"
                                   name="date_to"
                                   class="form-control"
                                   value="{{ $dateTo ?? '' }}"
                                   max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1 small"><i class="ti-search me-1"></i> Cari NIP/Nama</label>
                            <input type="text"
                                   name="search"
                                   id="searchInput"
                                   class="form-control"
                                   placeholder="Cari NIP/Nama..."
                                   value="{{ $search ?? '' }}"
                                   autocomplete="off">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="ti-filter"></i> Filter
                            </button>
                            @if($dateFrom || $dateTo || $search)
                                <a href="{{ route('aktivitas-pegawai.index') }}" class="btn btn-secondary">
                                    <i class="ti-reload"></i> Reset
                                </a>
                            @endif
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="ti-upload"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Filter Info -->
                @if($dateFrom || $dateTo)
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="ti-info-alt"></i>
                            Menampilkan data dari
                            <strong>{{ $dateFrom ? date('d/m/Y', strtotime($dateFrom)) : 'awal' }}</strong>
                            sampai
                            <strong>{{ $dateTo ? date('d/m/Y', strtotime($dateTo)) : 'akhir' }}</strong>
                        </small>
                    </div>
                @else
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="ti-info-alt"></i>
                            Periode Data Log:
                            <strong>{{ $stats['first_log'] }}</strong>
                            →
                            <strong>{{ $stats['last_log'] }}</strong>
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left">Total Pegawai Aktif</p>
                <div class="d-flex flex-wrap justify-content-between justify-content-md-center justify-content-xl-between align-items-center">
                    <h3 class="mb-0 mb-md-2 mb-xl-0 order-md-1 order-xl-0">{{ number_format($stats['total_pegawai']) }}</h3>
                    <i class="ti-user icon-md text-muted mb-0 mb-md-3 mb-xl-0"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left">Total Aktivitas</p>
                <div class="d-flex flex-wrap justify-content-between justify-content-md-center justify-content-xl-between align-items-center">
                    <h3 class="mb-0 mb-md-2 mb-xl-0 order-md-1 order-xl-0">{{ number_format($stats['total_aktivitas']) }}</h3>
                    <i class="ti-stats-up icon-md text-muted mb-0 mb-md-3 mb-xl-0"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left">Jenis Aktivitas</p>
                <div class="d-flex flex-wrap justify-content-between justify-content-md-center justify-content-xl-between align-items-center">
                    <h3 class="mb-0 mb-md-2 mb-xl-0 order-md-1 order-xl-0">{{ number_format($stats['total_kategori']) }}</h3>
                    <i class="ti-layers icon-md text-muted mb-0 mb-md-3 mb-xl-0"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left">Total Inject</p>
                <div class="d-flex flex-wrap justify-content-between justify-content-md-center justify-content-xl-between align-items-center">
                    <h3 class="mb-0 mb-md-2 mb-xl-0 order-md-1 order-xl-0">{{ number_format($stats['total_inject']) }}</h3>
                    <i class="ti-upload icon-md text-muted mb-0 mb-md-3 mb-xl-0"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left">Pegawai Belum Terdata</p>
                <div class="d-flex flex-wrap justify-content-between justify-content-md-center justify-content-xl-between align-items-center">
                    <h3 class="mb-0 mb-md-2 mb-xl-0 order-md-1 order-xl-0">{{ number_format($stats['pegawai_belum_terdata']) }}</h3>
                    <i class="ti-alert icon-md mb-0 mb-md-3 mb-xl-0"></i>
                </div>
                @if($stats['pegawai_belum_terdata'] > 0)
                    <a href="{{ route('staging.index') }}" class="btn btn-sm btn-light mt-2 w-100">
                        <i class="ti-eye"></i> Lihat Detail
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card bg-gradient-info text-white">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left">Efektivitas Kerja</p>
                <div class="d-flex flex-wrap justify-content-between justify-content-md-center justify-content-xl-between align-items-center">
                    <h3 class="mb-0 mb-md-2 mb-xl-0 order-md-1 order-xl-0"><i class="mdi mdi-chart-line"></i></h3>
                    <i class="mdi mdi-calculator icon-md mb-0 mb-md-3 mb-xl-0"></i>
                </div>
                <a href="{{ route('efektivitas-kerja.index') }}" class="btn btn-sm btn-light mt-2 w-100">
                    <i class="mdi mdi-chart-line me-1"></i> Hitung Efektivitas
                </a>
                <p class="small mt-2 mb-0 opacity-75">Mapping Non-Inject / Jam Kerja</p>
            </div>
        </div>
    </div>
</div>

<!-- Top 6 Kategori & Statistik PIC DMS -->
<div class="row">
    <!-- Top 6 Kategori Aktivitas (col-4) -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Kategori Aktivitas</h4>
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            id="btnExportPdf"
                            onclick="exportPdf()"
                            title="Export PDF">
                        <i class="ti-printer me-1"></i> Print Report
                    </button>
                </div>
                @php
                    $badgeColors = ['primary', 'success', 'info', 'warning', 'danger', 'dark', 'secondary'];
                @endphp
                @foreach($topKategori as $index => $kategori)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-{{ $badgeColors[$index % count($badgeColors)] }} me-2">
                                #{{ $index + 1 }}
                            </span>
                            <small class="text-muted">{{ $kategori->kategori_aktivitas }}</small>
                        </div>
                        <strong>{{ number_format($kategori->total) }}</strong>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $badgeColors[$index % count($badgeColors)] }}"
                             role="progressbar"
                             style="width: {{ ($kategori->total / $topKategori->first()->total) * 100 }}%">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Statistik Performa PIC DMS (col-8) -->
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Statistik Performa PIC DMS</h4>
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            id="btnExportPicPdf"
                            onclick="exportPicPdf()"
                            title="Export PDF PIC DMS">
                        <i class="ti-printer me-1"></i> Print Report
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Ketua PIC DMS</th>
                                <th class="text-center">Anggota</th>
                                <th class="text-end">Total Aktivitas</th>
                                <th class="text-end">Mapping</th>
                                <th class="text-end">Inject</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="picStatsTableBody">
                            <!-- Skeleton Loader -->
                            <tr>
                                <td colspan="7" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Memuat data statistik PIC DMS...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="picStatsPaginationInfo" class="text-muted small mt-3"></div>
                <div id="picStatsPaginationContainer" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Aktivitas Pegawai Table -->
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">Aktivitas Pegawai</h4>

                <!-- Loading Indicator -->
                <div id="tableLoading" style="display: none;" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Mencari data...</p>
                </div>

                <div id="tableContainer">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIP</th>
                                    <th>Nama</th>
                                    <th class="text-center">Jenis Aktivitas</th>
                                    <th class="text-center">Total Aktivitas</th>
                                    <th class="text-center">Last Activity</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- Skeleton Loader -->
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <span class="ms-2 text-muted">Memuat data aktivitas pegawai...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="paginationContainer">
                        <!-- Pagination will be loaded via AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mapping, Inject & Approval Dokumen Summary -->
<div class="row mb-2">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="ti-bar-chart text-primary me-2"></i>Rekap Aktivitas Dokumen</h4>
            <div>
                <button type="button" class="btn btn-primary btn-sm me-1" onclick="exportRekapSemuaAktivitasExcel()" id="btnExportRekapSemua">
                    <i class="ti-layout-list-post me-1"></i> Rekap Semua Aktivitas
                </button>
                <button type="button" class="btn btn-success btn-sm" onclick="exportAktivitasDokumenExcel()" id="btnExportAktivitasDokExcel">
                    <i class="ti-file me-1"></i> Export Per Kategori
                </button>
            </div>
        </div>
        <p class="text-muted small mt-1 mb-0">Ringkasan aktivitas Mapping, Inject, dan Approval dokumen per pegawai</p>
    </div>
</div>
<div class="row">
    <!-- Mapping Dokumen (Non-Inject) -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">
                        <i class="ti-files text-info me-2"></i>Mapping Dokumen
                    </h4>
                    <span class="badge badge-info">Non-Inject</span>
                </div>
                <p class="text-muted small mb-3">Mapping dokumen manual (tanpa inject) - Semua Pegawai</p>

                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-info">
                            <tr>
                                <th width="40">#</th>
                                <th>Nama Pegawai</th>
                                <th class="text-center" width="100">Per Dok</th>
                                <th class="text-center" width="100">Per PNS</th>
                            </tr>
                        </thead>
                        <tbody id="mappingTableBody">
                            <!-- Skeleton Loader -->
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Memuat data mapping dokumen...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="mappingPaginationContainer"></div>

                <!-- Summary -->
                <div id="mappingSummaryContainer" class="mt-3 pt-3 border-top" style="display:none;">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Total Halaman Ini</small>
                            <div>
                                <strong class="text-primary" id="mappingTotalDok">0</strong>
                                <span class="text-muted small">dok</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Per Object PNS</small>
                            <div>
                                <strong class="text-success" id="mappingTotalPNS">0</strong>
                                <span class="text-muted small">PNS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inject Dokumen (Inject - Unggah Dokumen) -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">
                        <i class="ti-upload text-warning me-2"></i>Inject Dokumen
                    </h4>
                    <span class="badge badge-warning">Inject</span>
                </div>
                <p class="text-muted small mb-3">Inject - Unggah Dokumen (details ≠ "unggah_dokumen") - Semua Pegawai</p>

                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-warning">
                            <tr>
                                <th width="40">#</th>
                                <th>Nama Pegawai</th>
                                <th class="text-center" width="100">Per Dok</th>
                                <th class="text-center" width="100">Per PNS</th>
                            </tr>
                        </thead>
                        <tbody id="injectTableBody">
                            <!-- Skeleton Loader -->
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Memuat data inject dokumen...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="injectPaginationContainer"></div>

                <!-- Summary -->
                <div id="injectSummaryContainer" class="mt-3 pt-3 border-top" style="display:none;">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Total Halaman Ini</small>
                            <div>
                                <strong class="text-primary" id="injectTotalDok">0</strong>
                                <span class="text-muted small">dok</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Per Object PNS</small>
                            <div>
                                <strong class="text-success" id="injectTotalPNS">0</strong>
                                <span class="text-muted small">PNS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Dokumen MyASN -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">
                        <i class="ti-check-box text-success me-2"></i>Approval Dokumen MyASN
                    </h4>
                    <span class="badge badge-success">Approval</span>
                </div>
                <p class="text-muted small mb-3">Approval upload dokumen MyASN - Semua Pegawai</p>

                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-success">
                            <tr>
                                <th width="40">#</th>
                                <th>Nama Pegawai</th>
                                <th class="text-center" width="100">Total</th>
                                <th class="text-center" width="100">Per PNS</th>
                            </tr>
                        </thead>
                        <tbody id="approvalTableBody">
                            <!-- Skeleton Loader -->
                            <tr>
                                <td colspan="4" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Memuat data approval dokumen...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="approvalPaginationContainer"></div>

                <!-- Summary -->
                <div id="approvalSummaryContainer" class="mt-3 pt-3 border-top" style="display:none;">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Total Halaman Ini</small>
                            <div>
                                <strong class="text-primary" id="approvalTotalDok">0</strong>
                                <span class="text-muted small">approval</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Per Object PNS</small>
                            <div>
                                <strong class="text-success" id="approvalTotalPNS">0</strong>
                                <span class="text-muted small">PNS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .pagination .page-link {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* Custom 5-column layout for Top 5 Kategori */
    @media (min-width: 768px) {
        .col-md-2-4 {
            flex: 0 0 20%;
            max-width: 20%;
        }
    }
</style>

<!-- Upload CSV Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">
                    <i class="ti-upload me-2"></i>Upload Log Aktivitas CSV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('aktivitas-pegawai.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info" role="alert">
                        <i class="ti-info-alt me-2"></i>
                        <strong>Petunjuk Upload:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Format file: CSV (.csv, .txt)</li>
                            <li>Maksimal ukuran: 100 MB</li>
                            <li>File akan diproses di background (queue)</li>
                            <li>Logs dengan NIP terdaftar akan masuk ke aktivitas utama</li>
                            <li>Logs dengan NIP belum terdaftar akan masuk ke staging</li>
                            <li>Summary akan otomatis di-update setelah proses selesai</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Pilih File CSV</label>
                        <input type="file"
                               class="form-control"
                               id="csv_file"
                               name="csv_file"
                               accept=".csv,.txt"
                               required>
                        <div class="form-text">
                            File CSV harus mengikuti format yang sama dengan data log aktivitas yang ada.
                        </div>
                    </div>

                    <div class="alert alert-warning" role="alert">
                        <i class="ti-alert me-2"></i>
                        <strong>Perhatian:</strong> Proses upload file besar mungkin membutuhkan waktu beberapa menit. Jangan tutup halaman selama proses berlangsung.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti-close"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none;">
    <div class="loading-content">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-light mt-3 mb-0">Memuat data...</p>
    </div>
</div>

<style>
    #loadingOverlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .loading-content {
        text-align: center;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const uploadForm = document.querySelector('#uploadModal form');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // Show loading when filter form is submitted
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            loadingOverlay.style.display = 'flex';
        });
    }

    // Show loading when upload form is submitted
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('csv_file');
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Pilih file CSV terlebih dahulu!');
                return false;
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
            if (modal) {
                modal.hide();
            }

            // Show loading overlay
            loadingOverlay.style.display = 'flex';
            const loadingText = loadingOverlay.querySelector('p');
            loadingText.textContent = 'Mengupload dan memproses file CSV... Mohon tunggu, ini mungkin memakan waktu beberapa menit.';
        });
    }

    // Show loading when reset button is clicked
    const resetButtons = document.querySelectorAll('a[href*="aktivitas-pegawai"]');
    resetButtons.forEach(button => {
        if (button.textContent.includes('Reset')) {
            button.addEventListener('click', function(e) {
                loadingOverlay.style.display = 'flex';
            });
        }
    });

    // Show loading when pagination is clicked
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function(e) {
            loadingOverlay.style.display = 'flex';
        });
    });

    // Hide loading when page is fully loaded (backup in case something goes wrong)
    window.addEventListener('load', function() {
        setTimeout(function() {
            loadingOverlay.style.display = 'none';
        }, 500);
    });
});

// AJAX Search for Aktivitas Pegawai
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('tableBody');
const tableContainer = document.getElementById('tableContainer');
const tableLoading = document.getElementById('tableLoading');
const paginationContainer = document.getElementById('paginationContainer');

if (searchInput) {
    let searchTimeout;

    // Function to perform AJAX search
    function performSearch(page = 1) {
        const searchValue = searchInput.value;
        const dateFrom = document.querySelector('input[name="date_from"]').value;
        const dateTo = document.querySelector('input[name="date_to"]').value;

        // Show loading
        tableContainer.style.opacity = '0.5';
        tableLoading.style.display = 'block';

        // Build URL with parameters
        const params = new URLSearchParams({
            search: searchValue,
            page: page
        });

        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        // Perform AJAX request
        fetch(`{{ route('api.aktivitas-pegawai.search') }}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update table body
            updateTableBody(data.data, searchValue);

            // Update pagination
            updatePagination(data.pagination, searchValue, dateFrom, dateTo);

            // Hide loading
            tableContainer.style.opacity = '1';
            tableLoading.style.display = 'none';
        })
        .catch(error => {
            console.error('Error:', error);
            tableContainer.style.opacity = '1';
            tableLoading.style.display = 'none';
        });
    }

    // Function to update table body
    function updateTableBody(data, searchValue) {
        if (data.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        ${searchValue ?
                            `Tidak ada data yang ditemukan untuk "${searchValue}"` :
                            'Belum ada data aktivitas'
                        }
                    </td>
                </tr>
            `;
        } else {
            let rows = '';
            data.forEach(item => {
                rows += `
                    <tr>
                        <td>${item.no}</td>
                        <td><code>${item.nip}</code></td>
                        <td>${item.nama}</td>
                        <td class="text-center">
                            <span class="badge badge-info">${item.jenis_aktivitas}</span>
                        </td>
                        <td class="text-center">
                            <strong>${item.total_aktivitas}</strong>
                        </td>
                        <td class="text-center">
                            <small class="text-muted">${item.last_activity}</small>
                        </td>
                        <td class="text-center">
                            <a href="${item.detail_url}" class="btn btn-sm btn-outline-primary">
                                <i class="ti-eye"></i> Detail
                            </a>
                        </td>
                    </tr>
                `;
            });
            tableBody.innerHTML = rows;
        }
    }

    // Function to update pagination
    function updatePagination(pagination, searchValue, dateFrom, dateTo) {
        if (pagination.last_page <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = `
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    Menampilkan ${pagination.from || 0} - ${pagination.to || 0} dari ${pagination.total} data
                </div>
                <div>
                    <nav>
                        <ul class="pagination mb-0">
        `;

        // Previous button
        if (pagination.current_page > 1) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="searchPage(${pagination.current_page - 1}); return false;">Previous</a>
                </li>
            `;
        }

        // Page numbers
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                paginationHTML += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="searchPage(${i}); return false;">${i}</a>
                    </li>
                `;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Next button
        if (pagination.current_page < pagination.last_page) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="searchPage(${pagination.current_page + 1}); return false;">Next</a>
                </li>
            `;
        }

        paginationHTML += `
                        </ul>
                    </nav>
                </div>
            </div>
        `;

        paginationContainer.innerHTML = paginationHTML;
    }

    // Expose search function to window for pagination clicks
    window.searchPage = function(page) {
        performSearch(page);
    };

    // Debounced search on input
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            performSearch(1);
        }, 500);
    });

    // Enter key support
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchTimeout);
            performSearch(1);
        }
    });
}

// Export PDF function with loading
function exportPdf() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = loadingOverlay.querySelector('p');
    const btnExportPdf = document.getElementById('btnExportPdf');

    // Show loading
    loadingOverlay.style.display = 'flex';
    loadingText.textContent = 'Generating PDF Report... Mohon tunggu, sedang memproses data aktivitas pegawai.';

    // Disable button
    btnExportPdf.disabled = true;
    btnExportPdf.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

    // Get current filter values
    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';
    const search = '{{ $search ?? '' }}';

    // Build URL with parameters
    let url = '{{ route("aktivitas-pegawai.export-pdf") }}';
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    if (search) params.append('search', search);

    if (params.toString()) {
        url += '?' + params.toString();
    }

    // Trigger download directly
    window.location.href = url;

    // Hide loading after 3 seconds (give time for PDF generation)
    setTimeout(function() {
        loadingOverlay.style.display = 'none';
        btnExportPdf.disabled = false;
        btnExportPdf.innerHTML = '<i class="ti-printer me-1"></i> Print Report';
    }, 3000);
}

// Export Rekap Semua Aktivitas (1 sheet, semua event)
function exportRekapSemuaAktivitasExcel() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = loadingOverlay.querySelector('p');
    const btn = document.getElementById('btnExportRekapSemua');

    loadingOverlay.style.display = 'flex';
    loadingText.textContent = 'Generating Excel... Mohon tunggu, sedang memproses rekap semua aktivitas.';

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';

    let url = '{{ route("aktivitas-pegawai.export-rekap-semua-excel") }}';
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    if (params.toString()) {
        url += '?' + params.toString();
    }

    window.location.href = url;

    setTimeout(function() {
        loadingOverlay.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="ti-layout-list-post me-1"></i> Rekap Semua Aktivitas';
    }, 3000);
}

// Export Aktivitas Dokumen Excel (Mapping, Inject, Approval)
function exportAktivitasDokumenExcel() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = loadingOverlay.querySelector('p');
    const btn = document.getElementById('btnExportAktivitasDokExcel');

    loadingOverlay.style.display = 'flex';
    loadingText.textContent = 'Generating Excel... Mohon tunggu, sedang memproses data aktivitas dokumen.';

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';

    let url = '{{ route("aktivitas-pegawai.export-aktivitas-dokumen-excel") }}';
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    if (params.toString()) {
        url += '?' + params.toString();
    }

    window.location.href = url;

    setTimeout(function() {
        loadingOverlay.style.display = 'none';
        btn.disabled = false;
        btn.innerHTML = '<i class="ti-file me-1"></i> Export Excel';
    }, 3000);
}

// Export PIC PDF function with loading
function exportPicPdf() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = loadingOverlay.querySelector('p');
    const btnExportPicPdf = document.getElementById('btnExportPicPdf');

    // Show loading
    loadingOverlay.style.display = 'flex';
    loadingText.textContent = 'Generating PDF Report PIC DMS... Mohon tunggu, sedang memproses data performa PIC.';

    // Disable button
    btnExportPicPdf.disabled = true;
    btnExportPicPdf.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

    // Get current filter values
    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';

    // Build URL with parameters
    let url = '{{ route("aktivitas-pegawai.export-pic-pdf") }}';
    const params = new URLSearchParams();
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    if (params.toString()) {
        url += '?' + params.toString();
    }

    // Trigger download directly
    window.location.href = url;

    // Hide loading after 5 seconds (PIC PDF takes longer)
    setTimeout(function() {
        loadingOverlay.style.display = 'none';
        btnExportPicPdf.disabled = false;
        btnExportPicPdf.innerHTML = '<i class="ti-printer me-1"></i> Print Report';
    }, 5000);
}

// ============================================
// LAZY LOADING - Load tables via AJAX
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Load Aktivitas Pegawai table
    loadAktivitasPegawai();

    // Load PIC Stats table with delay
    setTimeout(() => loadPicStats(), 200);

    // Load Mapping Dokumen table with delay
    setTimeout(() => loadMappingDokumen(), 700);

    // Load Inject Dokumen table with delay
    setTimeout(() => loadInjectDokumen(), 1200);

    // Load Approval Dokumen table with delay
    setTimeout(() => loadApprovalDokumen(), 1700);
});

function loadAktivitasPegawai(page = 1) {
    const tableBody = document.getElementById('tableBody');
    const paginationContainer = document.getElementById('paginationContainer');

    // Get filter values
    const search = '{{ $search ?? '' }}';
    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';

    // Build URL
    let url = '/api/monev-dms/search-aktivitas-pegawai?page=' + page;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (dateFrom) url += '&date_from=' + dateFrom;
    if (dateTo) url += '&date_to=' + dateTo;

    // Show loading
    tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span class="ms-2 text-muted">Memuat data aktivitas pegawai...</span>
            </td>
        </tr>
    `;

    // Fetch data
    fetch(url)
        .then(response => response.json())
        .then(result => {
            // Clear table
            tableBody.innerHTML = '';

            // Populate data
            if (result.data.length > 0) {
                result.data.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.no}</td>
                            <td><code>${item.nip}</code></td>
                            <td>${item.nama}</td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.jenis_aktivitas}</span>
                            </td>
                            <td class="text-center">
                                <strong>${item.total_aktivitas}</strong>
                            </td>
                            <td class="text-center">
                                <small class="text-muted">${item.last_activity}</small>
                            </td>
                            <td class="text-center">
                                <a href="${item.detail_url}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });

                // Show pagination
                if (result.pagination.last_page > 1) {
                    paginationContainer.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted small">
                                Menampilkan ${result.pagination.from} - ${result.pagination.to} dari ${result.pagination.total} data
                            </div>
                            <div>
                                ${generatePagination(result.pagination)}
                            </div>
                        </div>
                    `;
                }
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">
                            ${search ? 'Tidak ada data yang ditemukan untuk "' + search + '"' : 'Belum ada data aktivitas'}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading data:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-3">
                        <i class="mdi mdi-alert-circle"></i> Gagal memuat data. Silakan refresh halaman.
                    </td>
                </tr>
            `;
        });
}

function generatePagination(pagination) {
    let html = '<nav><ul class="pagination pagination-sm mb-0">';

    // Previous button
    if (pagination.current_page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadAktivitasPegawai(${pagination.current_page - 1}); return false;">«</a></li>`;
    }

    // Page numbers (show 5 pages max)
    let startPage = Math.max(1, pagination.current_page - 2);
    let endPage = Math.min(pagination.last_page, startPage + 4);

    for (let i = startPage; i <= endPage; i++) {
        const active = i === pagination.current_page ? 'active' : '';
        html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadAktivitasPegawai(${i}); return false;">${i}</a></li>`;
    }

    // Next button
    if (pagination.current_page < pagination.last_page) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadAktivitasPegawai(${pagination.current_page + 1}); return false;">»</a></li>`;
    }

    html += '</ul></nav>';
    return html;
}

// Load Mapping Dokumen table
function loadMappingDokumen(page = 1) {
    const tableBody = document.getElementById('mappingTableBody');
    const paginationContainer = document.getElementById('mappingPaginationContainer');
    const summaryContainer = document.getElementById('mappingSummaryContainer');

    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';
    const search = '{{ $search ?? '' }}';

    let url = '/api/monev-dms/mapping-dokumen?page=' + page;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (dateFrom) url += '&date_from=' + dateFrom;
    if (dateTo) url += '&date_to=' + dateTo;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            tableBody.innerHTML = '';

            if (result.data.length > 0) {
                let totalDok = 0, totalPNS = 0;

                result.data.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.no}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="${item.nama}">
                                    ${item.nama}
                                </div>
                                <small class="text-muted">${item.nip}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-primary">${item.total_per_dokumen}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success">${item.total_per_object_pns}</span>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;

                    totalDok += parseInt(item.total_per_dokumen.replace(/,/g, ''));
                    totalPNS += parseInt(item.total_per_object_pns.replace(/,/g, ''));
                });

                // Show summary
                document.getElementById('mappingTotalDok').textContent = totalDok.toLocaleString();
                document.getElementById('mappingTotalPNS').textContent = totalPNS.toLocaleString();
                summaryContainer.style.display = 'block';

                // Pagination with navigation buttons
                if (result.pagination.last_page > 1) {
                    let paginationHTML = '<nav class="mt-3"><ul class="pagination pagination-sm mb-0">';

                    // Previous button
                    if (result.pagination.current_page > 1) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadMappingDokumen(${result.pagination.current_page - 1}); return false;">«</a></li>`;
                    }

                    // Page numbers
                    let startPage = Math.max(1, result.pagination.current_page - 2);
                    let endPage = Math.min(result.pagination.last_page, startPage + 4);

                    for (let i = startPage; i <= endPage; i++) {
                        const active = i === result.pagination.current_page ? 'active' : '';
                        paginationHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadMappingDokumen(${i}); return false;">${i}</a></li>`;
                    }

                    // Next button
                    if (result.pagination.current_page < result.pagination.last_page) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadMappingDokumen(${result.pagination.current_page + 1}); return false;">»</a></li>`;
                    }

                    paginationHTML += '</ul></nav>';
                    paginationHTML += `<div class="text-muted small mt-2">Menampilkan ${result.pagination.from} - ${result.pagination.to} dari ${result.pagination.total} pegawai</div>`;
                    paginationContainer.innerHTML = paginationHTML;
                }
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            Tidak ada data mapping dokumen
                        </td>
                    </tr>
                `;
                summaryContainer.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading mapping dokumen:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger py-3">
                        <i class="mdi mdi-alert-circle"></i> Gagal memuat data
                    </td>
                </tr>
            `;
        });
}

// Load Inject Dokumen table
function loadInjectDokumen(page = 1) {
    const tableBody = document.getElementById('injectTableBody');
    const paginationContainer = document.getElementById('injectPaginationContainer');
    const summaryContainer = document.getElementById('injectSummaryContainer');

    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';
    const search = '{{ $search ?? '' }}';

    let url = '/api/monev-dms/inject-dokumen?page=' + page;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (dateFrom) url += '&date_from=' + dateFrom;
    if (dateTo) url += '&date_to=' + dateTo;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            tableBody.innerHTML = '';

            if (result.data.length > 0) {
                let totalDok = 0, totalPNS = 0;

                result.data.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.no}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="${item.nama}">
                                    ${item.nama}
                                </div>
                                <small class="text-muted">${item.nip}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-primary">${item.total_per_dokumen}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success">${item.total_per_object_pns}</span>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;

                    totalDok += parseInt(item.total_per_dokumen.replace(/,/g, ''));
                    totalPNS += parseInt(item.total_per_object_pns.replace(/,/g, ''));
                });

                // Show summary
                document.getElementById('injectTotalDok').textContent = totalDok.toLocaleString();
                document.getElementById('injectTotalPNS').textContent = totalPNS.toLocaleString();
                summaryContainer.style.display = 'block';

                // Pagination with navigation buttons
                if (result.pagination.last_page > 1) {
                    let paginationHTML = '<nav class="mt-3"><ul class="pagination pagination-sm mb-0">';

                    // Previous button
                    if (result.pagination.current_page > 1) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadInjectDokumen(${result.pagination.current_page - 1}); return false;">«</a></li>`;
                    }

                    // Page numbers
                    let startPage = Math.max(1, result.pagination.current_page - 2);
                    let endPage = Math.min(result.pagination.last_page, startPage + 4);

                    for (let i = startPage; i <= endPage; i++) {
                        const active = i === result.pagination.current_page ? 'active' : '';
                        paginationHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadInjectDokumen(${i}); return false;">${i}</a></li>`;
                    }

                    // Next button
                    if (result.pagination.current_page < result.pagination.last_page) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadInjectDokumen(${result.pagination.current_page + 1}); return false;">»</a></li>`;
                    }

                    paginationHTML += '</ul></nav>';
                    paginationHTML += `<div class="text-muted small mt-2">Menampilkan ${result.pagination.from} - ${result.pagination.to} dari ${result.pagination.total} pegawai</div>`;
                    paginationContainer.innerHTML = paginationHTML;
                }
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            Tidak ada data inject dokumen
                        </td>
                    </tr>
                `;
                summaryContainer.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading inject dokumen:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger py-3">
                        <i class="mdi mdi-alert-circle"></i> Gagal memuat data
                    </td>
                </tr>
            `;
        });
}

// Load Approval Dokumen table
function loadApprovalDokumen(page = 1) {
    const tableBody = document.getElementById('approvalTableBody');
    const paginationContainer = document.getElementById('approvalPaginationContainer');
    const summaryContainer = document.getElementById('approvalSummaryContainer');

    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';
    const search = '{{ $search ?? '' }}';

    let url = '/api/monev-dms/approval-dokumen?page=' + page;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (dateFrom) url += '&date_from=' + dateFrom;
    if (dateTo) url += '&date_to=' + dateTo;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            tableBody.innerHTML = '';

            if (result.data.length > 0) {
                let totalApproval = 0, totalPNS = 0;

                result.data.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.no}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="${item.nama}">
                                    ${item.nama}
                                </div>
                                <small class="text-muted">${item.nip}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success">${item.total_approval}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.total_per_object_pns}</span>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;

                    totalApproval += parseInt(item.total_approval.replace(/,/g, ''));
                    totalPNS += parseInt(item.total_per_object_pns.replace(/,/g, ''));
                });

                // Show summary
                document.getElementById('approvalTotalDok').textContent = totalApproval.toLocaleString();
                document.getElementById('approvalTotalPNS').textContent = totalPNS.toLocaleString();
                summaryContainer.style.display = 'block';

                // Pagination with navigation buttons
                if (result.pagination.last_page > 1) {
                    let paginationHTML = '<nav class="mt-3"><ul class="pagination pagination-sm mb-0">';

                    // Previous button
                    if (result.pagination.current_page > 1) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadApprovalDokumen(${result.pagination.current_page - 1}); return false;">«</a></li>`;
                    }

                    // Page numbers
                    let startPage = Math.max(1, result.pagination.current_page - 2);
                    let endPage = Math.min(result.pagination.last_page, startPage + 4);

                    for (let i = startPage; i <= endPage; i++) {
                        const active = i === result.pagination.current_page ? 'active' : '';
                        paginationHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadApprovalDokumen(${i}); return false;">${i}</a></li>`;
                    }

                    // Next button
                    if (result.pagination.current_page < result.pagination.last_page) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadApprovalDokumen(${result.pagination.current_page + 1}); return false;">»</a></li>`;
                    }

                    paginationHTML += '</ul></nav>';
                    paginationHTML += `<div class="text-muted small mt-2">Menampilkan ${result.pagination.from} - ${result.pagination.to} dari ${result.pagination.total} pegawai</div>`;
                    paginationContainer.innerHTML = paginationHTML;
                }
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            Tidak ada data approval dokumen
                        </td>
                    </tr>
                `;
                summaryContainer.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading approval dokumen:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger py-3">
                        <i class="mdi mdi-alert-circle"></i> Gagal memuat data
                    </td>
                </tr>
            `;
        });
}

function loadPicStats(page = 1) {
    const tableBody = document.getElementById('picStatsTableBody');
    const paginationContainer = document.getElementById('picStatsPaginationContainer');
    const paginationInfo = document.getElementById('picStatsPaginationInfo');

    const dateFrom = '{{ $dateFrom ?? '' }}';
    const dateTo = '{{ $dateTo ?? '' }}';
    const search = '{{ $search ?? '' }}';

    let url = '/api/monev-dms/pic-stats?page=' + page;
    if (search) url += '&search=' + encodeURIComponent(search);
    if (dateFrom) url += '&date_from=' + dateFrom;
    if (dateTo) url += '&date_to=' + dateTo;

    fetch(url)
        .then(response => response.json())
        .then(result => {
            tableBody.innerHTML = '';

            if (result.data.length > 0) {
                result.data.forEach(item => {
                    const nipInfo = item.ketua_nip ? `<br><small class="text-muted">NIP: ${item.ketua_nip}</small>` : '';
                    const row = `
                        <tr>
                            <td>${item.no}</td>
                            <td>
                                <strong>${item.ketua_nama}</strong>
                                ${nipInfo}
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.total_anggota}</span>
                            </td>
                            <td class="text-end">${item.total_aktivitas}</td>
                            <td class="text-end">${item.total_mapping}</td>
                            <td class="text-end">${item.total_inject}</td>
                            <td class="text-center">
                                <a href="${item.detail_url}"
                                   class="btn btn-sm btn-outline-info"
                                   title="Lihat Detail">
                                    <i class="ti-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });

                // Show pagination info
                if (result.pagination.total > 0) {
                    paginationInfo.innerHTML = `${result.pagination.from} - ${result.pagination.to} dari ${result.pagination.total}`;
                }

                // Pagination with navigation buttons
                if (result.pagination.last_page > 1) {
                    let paginationHTML = '<nav><ul class="pagination pagination-sm mb-0">';

                    // Previous button
                    if (result.pagination.current_page > 1) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadPicStats(${result.pagination.current_page - 1}); return false;">«</a></li>`;
                    }

                    // Page numbers
                    let startPage = Math.max(1, result.pagination.current_page - 2);
                    let endPage = Math.min(result.pagination.last_page, startPage + 4);

                    for (let i = startPage; i <= endPage; i++) {
                        const active = i === result.pagination.current_page ? 'active' : '';
                        paginationHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadPicStats(${i}); return false;">${i}</a></li>`;
                    }

                    // Next button
                    if (result.pagination.current_page < result.pagination.last_page) {
                        paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadPicStats(${result.pagination.current_page + 1}); return false;">»</a></li>`;
                    }

                    paginationHTML += '</ul></nav>';
                    paginationContainer.innerHTML = paginationHTML;
                }
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">
                            Belum ada data PIC DMS aktif
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading PIC stats:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-3">
                        <i class="mdi mdi-alert-circle"></i> Gagal memuat data
                    </td>
                </tr>
            `;
        });
}

</script>

@endsection
