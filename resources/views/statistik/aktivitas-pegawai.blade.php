@extends('layouts.app')

@section('title', 'Statistik Aktivitas Pegawai')

@section('content')

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
                                   class="form-control"
                                   placeholder="Cari NIP/Nama..."
                                   value="{{ $search ?? '' }}">
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
</div>

<!-- Top 5 Kategori & Statistik PIC DMS -->
<div class="row">
    <!-- Top 5 Kategori Aktivitas (col-4) -->
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Top 5 Kategori Aktivitas</h4>
                @foreach($topKategori as $index => $kategori)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index] }} me-2">
                                #{{ $index + 1 }}
                            </span>
                            <small class="text-muted">{{ $kategori->kategori_aktivitas }}</small>
                        </div>
                        <strong>{{ number_format($kategori->total) }}</strong>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index] }}"
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
                <h4 class="card-title mb-3">Statistik Performa PIC DMS</h4>

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
                        <tbody>
                            @forelse($picStats as $index => $pic)
                                <tr>
                                    <td>{{ $picStats->firstItem() + $index }}</td>
                                    <td>
                                        <strong>{{ $pic->ketua_nama ?: 'Tidak ada ketua' }}</strong>
                                        @if($pic->ketua_nip)
                                            <br><small class="text-muted">NIP: {{ $pic->ketua_nip }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $pic->total_anggota }}</span>
                                    </td>
                                    <td class="text-end">{{ number_format($pic->total_aktivitas) }}</td>
                                    <td class="text-end">{{ number_format($pic->total_mapping) }}</td>
                                    <td class="text-end">{{ number_format($pic->total_inject) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('pic.show', $pic->pic_id) }}"
                                           class="btn btn-sm btn-outline-info"
                                           title="Lihat Detail">
                                            <i class="ti-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Belum ada data PIC DMS aktif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($picStats->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        {{ $picStats->firstItem() }} - {{ $picStats->lastItem() }} dari {{ $picStats->total() }}
                    </div>
                    <div>
                        {{ $picStats->appends([
                            'search' => $search,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo
                        ])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
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
                        <tbody>
                            @forelse($aktivitas as $index => $a)
                                <tr>
                                    <td>{{ $aktivitas->firstItem() + $index }}</td>
                                    <td><code>{{ $a->nip }}</code></td>
                                    <td>{{ $a->nama }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $a->jenis_aktivitas }}</span>
                                    </td>
                                    <td class="text-center">
                                        <strong>{{ number_format($a->total_aktivitas) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $a->last_activity ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('aktivitas-pegawai.show', $a->nip) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        @if($search)
                                            Tidak ada data yang ditemukan untuk "{{ $search }}"
                                        @else
                                            Belum ada data aktivitas
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($aktivitas->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ $aktivitas->firstItem() }} - {{ $aktivitas->lastItem() }} dari {{ $aktivitas->total() }} data
                    </div>
                    <div>
                        {{ $aktivitas->appends(['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Mapping & Inject Dokumen Summary -->
<div class="row">
    <!-- Mapping Dokumen (Non-Inject) -->
    <div class="col-md-6 grid-margin stretch-card">
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
                        <tbody>
                            @forelse($mappingDokumen as $index => $item)
                                <tr>
                                    <td>{{ $mappingDokumen->firstItem() + $index }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $item->nama }}">
                                            {{ $item->nama }}
                                        </div>
                                        <small class="text-muted">{{ $item->nip }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">{{ number_format($item->total_per_dokumen) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ number_format($item->total_per_object_pns) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Tidak ada data mapping dokumen
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($mappingDokumen->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ $mappingDokumen->firstItem() }} - {{ $mappingDokumen->lastItem() }} dari {{ $mappingDokumen->total() }} pegawai
                    </div>
                    <div>
                        {{ $mappingDokumen->appends(['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'inject_page' => request('inject_page')])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif

                <!-- Summary -->
                <div class="mt-3 pt-3 border-top">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Total Halaman Ini</small>
                            <div>
                                <strong class="text-primary">{{ number_format($mappingDokumen->sum('total_per_dokumen')) }}</strong>
                                <span class="text-muted small">dok</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Per Object PNS</small>
                            <div>
                                <strong class="text-success">{{ number_format($mappingDokumen->sum('total_per_object_pns')) }}</strong>
                                <span class="text-muted small">PNS</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inject Dokumen (Inject - Unggah Dokumen) -->
    <div class="col-md-6 grid-margin stretch-card">
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
                        <tbody>
                            @forelse($injectDokumen as $index => $item)
                                <tr>
                                    <td>{{ $injectDokumen->firstItem() + $index }}</td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $item->nama }}">
                                            {{ $item->nama }}
                                        </div>
                                        <small class="text-muted">{{ $item->nip }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">{{ number_format($item->total_per_dokumen) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success">{{ number_format($item->total_per_object_pns) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Tidak ada data inject dokumen
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($injectDokumen->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ $injectDokumen->firstItem() }} - {{ $injectDokumen->lastItem() }} dari {{ $injectDokumen->total() }} pegawai
                    </div>
                    <div>
                        {{ $injectDokumen->appends(['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'mapping_page' => request('mapping_page')])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif

                <!-- Summary -->
                <div class="mt-3 pt-3 border-top">
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted d-block">Total Halaman Ini</small>
                            <div>
                                <strong class="text-primary">{{ number_format($injectDokumen->sum('total_per_dokumen')) }}</strong>
                                <span class="text-muted small">dok</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Per Object PNS</small>
                            <div>
                                <strong class="text-success">{{ number_format($injectDokumen->sum('total_per_object_pns')) }}</strong>
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
                            <li>Maksimal ukuran: 50 MB</li>
                            <li>Logs dengan NIP terdaftar akan masuk ke aktivitas utama</li>
                            <li>Logs dengan NIP belum terdaftar akan masuk ke staging</li>
                            <li>Summary akan otomatis di-update setelah upload</li>
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
    const loadingOverlay = document.getElementById('loadingOverlay');

    // Show loading when filter form is submitted
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            loadingOverlay.style.display = 'flex';
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
</script>

@endsection
