@extends('layouts.app')

@section('title', 'Dashboard DMS - Document Management System')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="home-tab">
            <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Overview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="dms-tab" data-bs-toggle="tab" href="#kelola-dms" role="tab" aria-selected="false">Kelola DMS Instansi</a>
                    </li>
                </ul>
            </div>
            <div class="tab-content tab-content-basic">
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="statistics-details d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="statistics-title">Total Upload</p>
                                    <h3 class="rate-percentage">{{ $stats['total_uploads'] }}</h3>
                                    <p class="text-muted"><small>File CSV</small></p>
                                </div>
                                <div>
                                    <p class="statistics-title">Total PNS</p>
                                    <h3 class="rate-percentage">{{ number_format($stats['total_pns']) }}</h3>
                                    <p class="text-muted"><small>Data Master</small></p>
                                </div>
                                <div>
                                    <p class="statistics-title">Instansi Calculated</p>
                                    <h3 class="rate-percentage">{{ $stats['total_instansi_calculated'] }}</h3>
                                    <p class="text-success d-flex"><i class="mdi mdi-check-circle"></i><span>Selesai</span></p>
                                </div>
                                <div class="d-none d-md-block">
                                    <p class="statistics-title">Sangat Lengkap</p>
                                    <h3 class="rate-percentage">{{ $kelengkapanDistribution->get('Sangat Lengkap')->total ?? 0 }}</h3>
                                    <p class="text-success d-flex"><i class="mdi mdi-star"></i><span>>90</span></p>
                                </div>
                                <div class="d-none d-md-block">
                                    <p class="statistics-title">Lengkap</p>
                                    <h3 class="rate-percentage">{{ $kelengkapanDistribution->get('Lengkap')->total ?? 0 }}</h3>
                                    <p class="text-primary d-flex"><i class="mdi mdi-check"></i><span>55.6-90</span></p>
                                </div>
                                <div class="d-none d-md-block">
                                    <p class="statistics-title">Kurang Lengkap</p>
                                    <h3 class="rate-percentage">{{ $kelengkapanDistribution->get('Kurang Lengkap')->total ?? 0 }}</h3>
                                    <p class="text-danger d-flex"><i class="mdi mdi-alert"></i><span><30</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- National Statistics Card -->
                    @if($nasionalScore)
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="card-title card-title-dash">Statistik Nasional DMS</h4>
                                        <small class="text-muted">Terakhir dihitung: {{ $nasionalScore->calculated_at->format('d M Y H:i') }}</small>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <p class="text-muted mb-1">Rata-rata Skor Nasional</p>
                                                <h2 class="mb-0 text-primary">{{ number_format($nasionalScore->avg_skor_nasional_system, 2) }}</h2>
                                                <small class="text-muted">Sistem</small> |
                                                <small class="text-success">CSV: {{ number_format($nasionalScore->avg_skor_nasional_csv, 2) }}</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <p class="text-muted mb-1">Total Instansi</p>
                                                <h2 class="mb-0">{{ number_format($nasionalScore->total_instansi) }}</h2>
                                                <small class="text-muted">Dihitung</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <div class="p-3 bg-light rounded">
                                                <p class="text-muted mb-1">Total PNS</p>
                                                <h2 class="mb-0">{{ number_format($nasionalScore->total_pns) }}</h2>
                                                <small class="text-muted">Data</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <div class="p-3 bg-success-subtle rounded border border-success">
                                                <p class="text-muted mb-1">Skor Tertinggi</p>
                                                <h2 class="mb-0 text-success">{{ number_format($nasionalScore->max_skor_instansi, 2) }}</h2>
                                                <small class="text-muted">Instansi</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <div class="p-3 bg-danger-subtle rounded border border-danger">
                                                <p class="text-muted mb-1">Skor Terendah</p>
                                                <h2 class="mb-0 text-danger">{{ number_format($nasionalScore->min_skor_instansi, 2) }}</h2>
                                                <small class="text-muted">Instansi</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <h6 class="mb-2">Distribusi Kelengkapan Nasional:</h6>
                                            <div class="d-flex gap-3">
                                                <div class="badge badge-success px-3 py-2">
                                                    Sangat Lengkap: {{ number_format($nasionalScore->count_sangat_lengkap) }}
                                                </div>
                                                <div class="badge badge-primary px-3 py-2">
                                                    Lengkap: {{ number_format($nasionalScore->count_lengkap) }}
                                                </div>
                                                <div class="badge badge-warning px-3 py-2">
                                                    Cukup Lengkap: {{ number_format($nasionalScore->count_cukup_lengkap) }}
                                                </div>
                                                <div class="badge badge-danger px-3 py-2">
                                                    Kurang Lengkap: {{ number_format($nasionalScore->count_kurang_lengkap) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Bar Charts & Pie Chart -->
                    <div class="row">
                        <div class="col-lg-4 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Top 5 Instansi Tertinggi</h4>
                                    <div class="chartjs-wrapper mt-3">
                                        <canvas id="topInstansiChart" height="180"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Top 5 Instansi Terendah</h4>
                                    <div class="chartjs-wrapper mt-3">
                                        <canvas id="bottomInstansiChart" height="180"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Distribusi Kelengkapan</h4>
                                    <div class="chartjs-wrapper mt-3">
                                        <canvas id="kelengkapanPieChart" height="180"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8 d-flex flex-column">
                            <div class="row flex-grow">
                                <div class="col-12 grid-margin stretch-card">
                                    <div class="card card-rounded">
                                        <div class="card-body">
                                            <h4 class="card-title mb-3">Instansi yang Sudah Dihitung</h4>

                                            <!-- Search Form -->
                                            <form method="GET" action="{{ route('dashboard.dms') }}" class="mb-3">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="search" placeholder="Cari nama instansi..." value="{{ $search ?? '' }}">
                                                    <button class="btn btn-primary" type="submit">
                                                        <i class="mdi mdi-magnify"></i> Cari
                                                    </button>
                                                    @if($search ?? false)
                                                        <a href="{{ route('dashboard.dms') }}" class="btn btn-secondary">
                                                            <i class="mdi mdi-reload"></i> Reset
                                                        </a>
                                                    @endif
                                                </div>
                                            </form>

                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>Instansi</th>
                                                            <th class="text-center">Total PNS</th>
                                                            <th class="text-center">Skor System</th>
                                                            <th class="text-center">Status</th>
                                                            <th class="text-center">Update</th>
                                                            <th class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($calculatedInstansi as $index => $inst)
                                                            @php
                                                                $kelengkapanBadge = match($inst->status_kelengkapan) {
                                                                    'Sangat Lengkap' => 'badge-success',
                                                                    'Lengkap' => 'badge-primary',
                                                                    'Cukup Lengkap' => 'badge-warning',
                                                                    'Kurang Lengkap' => 'badge-danger',
                                                                    default => 'badge-secondary'
                                                                };
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $calculatedInstansi->firstItem() + $index }}</td>
                                                                <td><strong>{{ $inst->instansi_nama }}</strong></td>
                                                                <td class="text-center">
                                                                    <span class="badge badge-info">{{ number_format($inst->total_pns) }}</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <strong>{{ number_format($inst->skor_instansi_calculated_system, 2) }}</strong>
                                                                </td>
                                                                <td class="text-center">
                                                                    <span class="badge {{ $kelengkapanBadge }}">{{ $inst->status_kelengkapan }}</span>
                                                                </td>
                                                                <td class="text-center">
                                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($inst->calculated_at)->diffForHumans() }}</small>
                                                                </td>
                                                                <td class="text-center">
                                                                    <a href="{{ route('dms.instansi.detail-full', $inst->instansi_id) }}" class="btn btn-sm btn-outline-primary">
                                                                        <i class="mdi mdi-eye"></i> Detail
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="7" class="text-center text-muted py-3">
                                                                    @if($search ?? false)
                                                                        Tidak ada data yang ditemukan untuk "{{ $search }}"
                                                                    @else
                                                                        Belum ada instansi yang dihitung
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>

                                            @if($calculatedInstansi->hasPages())
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div class="text-muted small">
                                                    Menampilkan {{ $calculatedInstansi->firstItem() }} - {{ $calculatedInstansi->lastItem() }} dari {{ $calculatedInstansi->total() }} instansi
                                                </div>
                                                <div>
                                                    {{ $calculatedInstansi->appends(['search' => $search])->links('pagination::bootstrap-5') }}
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 d-flex flex-column">
                            <div class="row flex-grow">
                                <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                    <div class="card bg-primary card-rounded">
                                        <div class="card-body pb-0">
                                            <h4 class="card-title card-title-dash text-white mb-4">Distribusi Skor</h4>
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between text-white">
                                                            <p class="mb-1">Sangat Baik (80-100)</p>
                                                            <p class="mb-1">{{ $scoreDistribution->sangat_baik }}</p>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-success" style="width: {{ $stats['total_pns'] > 0 ? ($scoreDistribution->sangat_baik / $stats['total_pns']) * 100 : 0 }}%"></div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between text-white">
                                                            <p class="mb-1">Baik (60-79)</p>
                                                            <p class="mb-1">{{ $scoreDistribution->baik }}</p>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-info" style="width: {{ $stats['total_pns'] > 0 ? ($scoreDistribution->baik / $stats['total_pns']) * 100 : 0 }}%"></div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between text-white">
                                                            <p class="mb-1">Cukup (40-59)</p>
                                                            <p class="mb-1">{{ $scoreDistribution->cukup }}</p>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-warning" style="width: {{ $stats['total_pns'] > 0 ? ($scoreDistribution->cukup / $stats['total_pns']) * 100 : 0 }}%"></div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-0">
                                                        <div class="d-flex justify-content-between text-white">
                                                            <p class="mb-1">Kurang (<40)</p>
                                                            <p class="mb-1">{{ $scoreDistribution->kurang }}</p>
                                                        </div>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar bg-danger" style="width: {{ $stats['total_pns'] > 0 ? ($scoreDistribution->kurang / $stats['total_pns']) * 100 : 0 }}%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                    <div class="card card-rounded">
                                        <div class="card-body">
                                            <h5 class="card-title">Upload Terbaru</h5>
                                            @if($stats['latest_upload'])
                                                <div class="mt-3">
                                                    <p class="mb-1"><strong>{{ $stats['latest_upload']->filename }}</strong></p>
                                                    <p class="text-muted mb-1"><small>{{ $stats['latest_upload']->upload_date->format('d M Y, H:i') }}</small></p>
                                                    <p class="mb-1">Total Records: <strong>{{ number_format($stats['latest_upload']->total_records) }}</strong></p>
                                                    <span class="badge
                                                        @if($stats['latest_upload']->status == 'completed') bg-success
                                                        @elseif($stats['latest_upload']->status == 'processing') bg-warning
                                                        @else bg-secondary
                                                        @endif
                                                    ">{{ ucfirst($stats['latest_upload']->status) }}</span>
                                                </div>
                                            @else
                                                <p class="text-muted">Belum ada upload</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 d-flex flex-column">
                            <div class="row flex-grow">
                                <div class="col-12 grid-margin stretch-card">
                                    <div class="card card-rounded">
                                        <div class="card-body">
                                            <div class="d-sm-flex justify-content-between align-items-start">
                                                <div>
                                                    <h4 class="card-title card-title-dash">Proyek Terbaru</h4>
                                                    <p class="card-subtitle card-subtitle-dash">Daftar proyek yang baru ditambahkan</p>
                                                </div>
                                                <div>
                                                    <button class="btn btn-primary btn-sm text-white mb-0 me-0" type="button">Lihat Semua</button>
                                                </div>
                                            </div>
                                            <div class="table-responsive mt-1">
                                                <table class="table select-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Nama Proyek</th>
                                                            <th>Status</th>
                                                            <th>Progress</th>
                                                            <th>Deadline</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <div>
                                                                        <h6>Proyek Website Instansi</h6>
                                                                        <p>Development & Design</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="badge badge-opacity-success">Aktif</div>
                                                            </td>
                                                            <td>
                                                                <div class="progress">
                                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </td>
                                                            <td>15 Feb 2026</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary">Detail</button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <div>
                                                                        <h6>Aplikasi Mobile</h6>
                                                                        <p>Mobile Development</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="badge badge-opacity-warning">Review</div>
                                                            </td>
                                                            <td>
                                                                <div class="progress">
                                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </td>
                                                            <td>28 Feb 2026</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary">Detail</button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <div>
                                                                        <h6>Sistem Informasi</h6>
                                                                        <p>Backend System</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="badge badge-opacity-info">Planning</div>
                                                            </td>
                                                            <td>
                                                                <div class="progress">
                                                                    <div class="progress-bar bg-info" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </td>
                                                            <td>10 Mar 2026</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary">Detail</button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Kelola DMS Instansi -->
                <div class="tab-pane fade" id="kelola-dms" role="tabpanel" aria-labelledby="dms-tab">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Upload Data DMS PNS</h4>

                                    @if(session('success'))
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <form id="uploadForm" action="{{ route('dms.upload') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">Select CSV File</label>
                                            <input type="file" name="csv_file" id="csvFileInput" class="form-control" accept=".csv" required>
                                            @error('csv_file')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                            <small class="text-muted">Format: CSV dengan kolom id, nip, nama, status_cpns_pns, status_arsip, skor_arsip_2026, instansi_induk_id, instansi_nama</small>
                                        </div>
                                        <button type="submit" id="uploadBtn" class="btn btn-primary">
                                            <i class="mdi mdi-upload"></i> Upload CSV
                                        </button>
                                    </form>

                                    <!-- Upload Progress Alert -->
                                    <div id="uploadProgressAlert" class="alert alert-info mt-3" style="display: none;">
                                        <h5><i class="mdi mdi-cloud-upload"></i> Uploading File...</h5>
                                        <p class="mb-2">Please wait while we process your CSV file.</p>
                                        <div class="progress" style="height: 25px;">
                                            <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                                 role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                <span id="uploadProgressText">0%</span>
                                            </div>
                                        </div>
                                        <small class="text-muted mt-2 d-block" id="uploadProgressStatus">Uploading file to server...</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Upload History</h4>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Upload Date</th>
                                                    <th>Filename</th>
                                                    <th>Total Records</th>
                                                    <th>Processed</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($uploads ?? [] as $upload)
                                                    <tr data-upload-id="{{ $upload->id }}" class="upload-row">
                                                        <td>{{ $upload->upload_date->format('d M Y H:i') }}</td>
                                                        <td>{{ $upload->filename }}</td>
                                                        <td class="upload-total">{{ number_format($upload->total_records) }}</td>
                                                        <td class="upload-progress-cell">
                                                            @if($upload->status === 'processing')
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-grow-1 me-2">
                                                                        <div class="progress" style="height: 20px;">
                                                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info upload-progress-bar"
                                                                                 role="progressbar"
                                                                                 style="width: {{ $upload->getProgressPercentage() }}%"
                                                                                 aria-valuenow="{{ $upload->getProgressPercentage() }}"
                                                                                 aria-valuemin="0"
                                                                                 aria-valuemax="100">
                                                                                <span class="upload-percentage">{{ $upload->getProgressPercentage() }}%</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <small class="text-muted upload-processed">{{ number_format($upload->processed_records) }} / {{ number_format($upload->total_records) }}</small>
                                                                </div>
                                                            @else
                                                                <span class="upload-processed">{{ number_format($upload->processed_records) }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="upload-status-cell">
                                                            @if($upload->status === 'completed')
                                                                <span class="badge bg-success">Completed</span>
                                                            @elseif($upload->status === 'processing')
                                                                <span class="badge bg-warning">Processing...</span>
                                                            @elseif($upload->status === 'failed')
                                                                <span class="badge bg-danger">Failed</span>
                                                            @else
                                                                <span class="badge bg-secondary">Pending</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($upload->status === 'completed')
                                                                <a href="{{ route('dms.show', $upload->id) }}" class="btn btn-sm btn-primary">
                                                                    <i class="mdi mdi-eye"></i> View Details
                                                                </a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center">No uploads yet</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if(isset($uploads))
                                        <div class="mt-3">
                                            {{ $uploads->links('pagination::bootstrap-5') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.pagination {
    --bs-pagination-padding-x: 0.5rem !important;
    --bs-pagination-padding-y: 0.25rem !important;
    --bs-pagination-font-size: 0.875rem !important;
    --bs-pagination-border-color: #dee2e6 !important;
    --bs-pagination-color: #6c757d !important;
    margin-bottom: 0 !important;
}
.pagination .page-link {
    border-color: #dee2e6 !important;
    color: #6c757d !important;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #fff !important;
}
.pagination .page-link:hover {
    background-color: #e9ecef !important;
    color: #0d6efd !important;
}
</style>
@endpush

@push('plugin-scripts')
<script src="{{ asset('assets/vendors/chart.js/chart.umd.js') }}"></script>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Top Instansi Bar Chart (VERTICAL)
    if ($("#topInstansiChart").length) {
        var ctx = document.getElementById('topInstansiChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($topInstansi->pluck('instansi_nama')->map(function($nama) {
                    return strlen($nama) > 15 ? substr($nama, 0, 15) . '...' : $nama;
                })),
                datasets: [{
                    label: 'Skor',
                    data: @json($topInstansi->pluck('skor_instansi_calculated_system')),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Skor: ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: "rgba(0, 0, 0, 0.05)" },
                        ticks: { color: "#9ca2a9" }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: "#9ca2a9", font: { size: 9 }, maxRotation: 45, minRotation: 45 }
                    }
                }
            }
        });
    }

    // Bottom Instansi Bar Chart (VERTICAL)
    if ($("#bottomInstansiChart").length) {
        var ctx = document.getElementById('bottomInstansiChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($bottomInstansi->pluck('instansi_nama')->map(function($nama) {
                    return strlen($nama) > 15 ? substr($nama, 0, 15) . '...' : $nama;
                })),
                datasets: [{
                    label: 'Skor',
                    data: @json($bottomInstansi->pluck('skor_instansi_calculated_system')),
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Skor: ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: "rgba(0, 0, 0, 0.05)" },
                        ticks: { color: "#9ca2a9" }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: "#9ca2a9", font: { size: 9 }, maxRotation: 45, minRotation: 45 }
                    }
                }
            }
        });
    }

    // Pie Chart - Kelengkapan Distribution
    if ($("#kelengkapanPieChart").length) {
        var ctx = document.getElementById('kelengkapanPieChart').getContext('2d');

        @php
            $sangatLengkap = $kelengkapanDistribution->get('Sangat Lengkap')->total ?? 0;
            $lengkap = $kelengkapanDistribution->get('Lengkap')->total ?? 0;
            $cukupLengkap = $kelengkapanDistribution->get('Cukup Lengkap')->total ?? 0;
            $kurangLengkap = $kelengkapanDistribution->get('Kurang Lengkap')->total ?? 0;
            $totalPns = $stats['total_pns'];
        @endphp

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Sangat Lengkap', 'Lengkap', 'Cukup Lengkap', 'Kurang Lengkap'],
                datasets: [{
                    data: [{{ $sangatLengkap }}, {{ $lengkap }}, {{ $cukupLengkap }}, {{ $kurangLengkap }}],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(13, 110, 253, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(13, 110, 253, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = {{ $totalPns }};
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Handle Upload Form Submission with AJAX
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const uploadBtn = document.getElementById('uploadBtn');
        const progressAlert = document.getElementById('uploadProgressAlert');
        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        const progressStatus = document.getElementById('uploadProgressStatus');

        // Disable button and show progress
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';
        progressAlert.style.display = 'block';
        progressBar.style.width = '10%';
        progressText.textContent = '10%';
        progressStatus.textContent = 'Uploading file to server...';

        // Upload file with XMLHttpRequest to track upload progress
        const xhr = new XMLHttpRequest();

        // Track upload progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = percentComplete + '%';
                progressStatus.textContent = `Uploading: ${formatBytes(e.loaded)} / ${formatBytes(e.total)}`;
            }
        });

        // Handle completion
        xhr.addEventListener('load', function() {
            if (xhr.status === 200 || xhr.status === 302) {
                progressBar.classList.remove('bg-primary');
                progressBar.classList.add('bg-success');
                progressBar.style.width = '100%';
                progressText.textContent = '100%';
                progressStatus.textContent = 'Upload complete! Processing data...';

                // Start polling for processing progress
                setTimeout(function() {
                    progressAlert.style.display = 'none';
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="mdi mdi-upload"></i> Upload CSV';

                    // Reload to show the upload in history
                    location.reload();
                }, 2000);
            } else {
                progressBar.classList.remove('bg-primary');
                progressBar.classList.add('bg-danger');
                progressStatus.textContent = 'Upload failed! Please try again.';
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="mdi mdi-upload"></i> Upload CSV';
            }
        });

        // Handle errors
        xhr.addEventListener('error', function() {
            progressBar.classList.remove('bg-primary');
            progressBar.classList.add('bg-danger');
            progressStatus.textContent = 'Upload failed! Please try again.';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="mdi mdi-upload"></i> Upload CSV';
        });

        // Send request
        xhr.open('POST', form.action);
        xhr.send(formData);
    });

    // Format bytes helper function
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // AJAX Progress Polling for DMS Upload
    function pollUploadProgress() {
        const processingRows = document.querySelectorAll('.upload-row');

        processingRows.forEach(row => {
            const uploadId = row.dataset.uploadId;
            const statusBadge = row.querySelector('.upload-status-cell .badge');

            // Only poll if status is processing or pending
            if (statusBadge && (statusBadge.textContent.includes('Processing') || statusBadge.textContent.includes('Pending'))) {
                fetch(`/dms/${uploadId}/progress`)
                    .then(response => response.json())
                    .then(data => {
                        // Update progress bar
                        const progressBar = row.querySelector('.upload-progress-bar');
                        const percentage = row.querySelector('.upload-percentage');
                        const processed = row.querySelector('.upload-processed');
                        const progressCell = row.querySelector('.upload-progress-cell');
                        const statusCell = row.querySelector('.upload-status-cell');

                        if (data.status === 'processing') {
                            // Update progress bar if exists
                            if (progressBar) {
                                progressBar.style.width = data.percentage + '%';
                                progressBar.setAttribute('aria-valuenow', data.percentage);
                            }

                            // Update percentage text
                            if (percentage) {
                                percentage.textContent = data.percentage + '%';
                            }

                            // Update processed count
                            if (processed) {
                                processed.textContent = data.processed.toLocaleString() + ' / ' + data.total.toLocaleString();
                            }

                            // If progress bar doesn't exist yet, create it
                            if (!progressBar && progressCell) {
                                const total = row.querySelector('.upload-total').textContent.replace(/,/g, '');
                                progressCell.innerHTML = `
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 me-2">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info upload-progress-bar"
                                                     role="progressbar"
                                                     style="width: ${data.percentage}%"
                                                     aria-valuenow="${data.percentage}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    <span class="upload-percentage">${data.percentage}%</span>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted upload-processed">${data.processed.toLocaleString()} / ${data.total.toLocaleString()}</small>
                                    </div>
                                `;
                            }

                            // Update status badge
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-warning">Processing...</span>';
                            }
                        } else if (data.status === 'completed') {
                            // Reload page when completed to show the "View Details" button
                            location.reload();
                        } else if (data.status === 'failed') {
                            // Update to failed status
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-danger">Failed</span>';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error polling upload progress:', error);
                    });
            }
        });
    }

    // Poll every 2 seconds
    setInterval(pollUploadProgress, 2000);

    // Initial poll on page load
    pollUploadProgress();

    // Dashboard Chart
    if ($("#dashboardChart").length) {
        var ctx = document.getElementById('dashboardChart').getContext("2d");
        var gradientStrokeViolet = ctx.createLinearGradient(0, 0, 0, 181);
        gradientStrokeViolet.addColorStop(0, 'rgba(218, 140, 255, 1)');
        gradientStrokeViolet.addColorStop(1, 'rgba(154, 85, 255, 1)');

        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Proyek Selesai',
                    data: [5, 8, 12, 15, 18, 21, 24, 28, 30, 32, 35, 38],
                    backgroundColor: gradientStrokeViolet,
                    borderColor: [
                        '#9B51E0',
                    ],
                    borderWidth: 2,
                    fill: true,
                    pointBorderColor: "#fff",
                    pointBackgroundColor: "#9B51E0",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            display: true,
                            color: "rgba(0, 0, 0, 0.05)"
                        },
                        ticks: {
                            color: "#9ca2a9"
                        }
                    },
                    x: {
                        border: {
                            display: false
                        },
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: "#9ca2a9"
                        }
                    }
                }
            }
        });
    }
</script>
@endpush

