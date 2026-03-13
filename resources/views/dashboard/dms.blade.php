@extends('layouts.app')

@section('title', 'Dashboard DMS - Document Management System')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="home-tab">
            <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active ps-0" id="monev-skor-tab" data-bs-toggle="tab" href="#monev-skor" role="tab" aria-controls="monev-skor" aria-selected="true">Monev Skor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="detail-skor-tab" data-bs-toggle="tab" href="#detail-skor" role="tab" aria-controls="detail-skor" aria-selected="false">Detail Skor Perorang dan Instansi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="kelola-dms-tab" data-bs-toggle="tab" href="#kelola-dms" role="tab" aria-selected="false">Kelola Statistik Instansi</a>
                    </li>
                </ul>
            </div>
            <div class="tab-content tab-content-basic">
                <!-- Tab: Monev Skor -->
                <div class="tab-pane fade show active" id="monev-skor" role="tabpanel" aria-labelledby="monev-skor">

                    <!-- SECTION 1: PERBANDINGAN PERIODE -->
                    @if($monevUploads->count() >= 2)
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info border-0 mb-3" style="background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%); border-left: 5px solid #2196F3 !important;">
                                <h4 class="mb-0 fw-bold text-primary">
                                    <i class="mdi mdi-numeric-1-circle"></i> SECTION 1: PERBANDINGAN PERIODE
                                </h4>
                                <small class="text-muted">Bandingkan data monitoring antara dua periode berbeda</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border-info" style="border-width: 2px !important; box-shadow: 0 4px 8px rgba(33, 150, 243, 0.2);">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="mdi mdi-compare text-info"></i> Filter Perbandingan Periode
                                    </h5>
                                    <form id="comparisonForm" class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold small">
                                                <i class="mdi mdi-calendar-clock"></i> Periode Awal (Sebelum)
                                            </label>
                                            <select id="compare_period_start" name="compare_period_start" class="form-select">
                                                <option value="">-- Pilih Periode Awal --</option>
                                                @foreach($monevUploads as $upload)
                                                    <option value="{{ $upload->upload_date }}">
                                                        {{ \Carbon\Carbon::parse($upload->upload_date)->format('d M Y') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold small">
                                                <i class="mdi mdi-calendar-check"></i> Periode Akhir (Sekarang)
                                            </label>
                                            <select id="compare_period_end" name="compare_period_end" class="form-select">
                                                <option value="">-- Pilih Periode Akhir --</option>
                                                @foreach($monevUploads as $upload)
                                                    <option value="{{ $upload->upload_date }}" {{ $loop->first ? 'selected' : '' }}>
                                                        {{ \Carbon\Carbon::parse($upload->upload_date)->format('d M Y') }}
                                                        @if($loop->first)
                                                            (Terbaru)
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" id="btnCompare" class="btn btn-info w-100">
                                                <i class="mdi mdi-magnify"></i> Bandingkan
                                            </button>
                                        </div>
                                    </form>
                                    <div class="mt-2" id="resetComparisonBtn" style="display: none;">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="resetComparison()">
                                            <i class="mdi mdi-reload"></i> Reset Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Period Comparison Analysis -->
                    <div id="comparisonContainer" style="display: none;">
                    @if(false) {{-- Never show on initial page load --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-rounded">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h4 class="card-title mb-1">
                                                <i class="mdi mdi-chart-timeline-variant text-info"></i> Analisis Perbandingan Periode
                                            </h4>
                                            <small class="text-muted">
                                                Periode {{ \Carbon\Carbon::parse($comparisonData['previous_period'])->format('d M Y') }} vs {{ \Carbon\Carbon::parse($comparisonData['current_period'])->format('d M Y') }}
                                            </small>
                                        </div>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('dashboard.monev.export-comparison-excel', ['previous_date' => $comparisonData['previous_period'], 'current_date' => $comparisonData['current_period']]) }}"
                                               class="btn btn-sm btn-success"
                                               title="Download Excel">
                                                <i class="mdi mdi-file-excel"></i> Excel
                                            </a>
                                            <a href="{{ route('dashboard.monev.export-comparison-pdf', ['previous_date' => $comparisonData['previous_period'], 'current_date' => $comparisonData['current_period']]) }}"
                                               class="btn btn-sm btn-danger"
                                               target="_blank"
                                               title="Download PDF">
                                                <i class="mdi mdi-file-pdf"></i> PDF
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Trend Summary Cards -->
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="card border-success">
                                                <div class="card-body text-center py-3">
                                                    <i class="mdi mdi-trending-up text-success" style="font-size: 2rem;"></i>
                                                    <h3 class="text-success mt-2 mb-1">{{ $comparisonData['count_naik'] }}</h3>
                                                    <p class="text-muted mb-0 small">Instansi Mengalami Kenaikan</p>
                                                    <small class="text-muted">(Naik > 0.5 poin)</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border-warning">
                                                <div class="card-body text-center py-3">
                                                    <i class="mdi mdi-minus-circle text-warning" style="font-size: 2rem;"></i>
                                                    <h3 class="text-warning mt-2 mb-1">{{ $comparisonData['count_stagnan'] }}</h3>
                                                    <p class="text-muted mb-0 small">Instansi Stagnan/Stabil</p>
                                                    <small class="text-muted">(Perubahan ≤ 0.5 poin)</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card border-danger">
                                                <div class="card-body text-center py-3">
                                                    <i class="mdi mdi-trending-down text-danger" style="font-size: 2rem;"></i>
                                                    <h3 class="text-danger mt-2 mb-1">{{ $comparisonData['count_turun'] }}</h3>
                                                    <p class="text-muted mb-0 small">Instansi Mengalami Penurunan</p>
                                                    <small class="text-muted">(Turun > 0.5 poin)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Comparison Data Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="40" class="text-center">#</th>
                                                    <th>Nama Instansi</th>
                                                    <th width="100" class="text-center">{{ \Carbon\Carbon::parse($comparisonData['previous_period'])->format('d M Y') }}</th>
                                                    <th width="100" class="text-center">{{ \Carbon\Carbon::parse($comparisonData['current_period'])->format('d M Y') }}</th>
                                                    <th width="100" class="text-center">Perubahan</th>
                                                    <th width="100" class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    // Combine all comparison data and sort by change (descending)
                                                    $allComparisons = collect($comparisonData['all_comparisons'] ?? [])
                                                        ->sortByDesc('perubahan')
                                                        ->values();
                                                @endphp
                                                @foreach($allComparisons as $index => $change)
                                                @php
                                                    // Stagnan hanya jika benar-benar 0
                                                    $statusBadge = 'badge-warning';
                                                    $statusText = 'Stagnan';
                                                    $statusIcon = 'mdi-minus';

                                                    if ($change['perubahan'] > 0) {
                                                        $statusBadge = 'badge-success';
                                                        $statusText = 'Naik';
                                                        $statusIcon = 'mdi-trending-up';
                                                    } elseif ($change['perubahan'] < 0) {
                                                        $statusBadge = 'badge-danger';
                                                        $statusText = 'Turun';
                                                        $statusIcon = 'mdi-trending-down';
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                                    <td>{{ $change['nama_instansi'] }}</td>
                                                    <td class="text-center">
                                                        <span class="badge badge-secondary">{{ number_format($change['skor_sebelum'], 2) }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-info">{{ number_format($change['skor_sekarang'], 2) }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($change['perubahan'] > 0)
                                                            <span class="badge badge-success">
                                                                <i class="mdi mdi-arrow-up"></i> +{{ number_format($change['perubahan'], 2) }}
                                                            </span>
                                                        @elseif($change['perubahan'] < 0)
                                                            <span class="badge badge-danger">
                                                                <i class="mdi mdi-arrow-down"></i> {{ number_format($change['perubahan'], 2) }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary">0.00</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $statusBadge }}">
                                                            <i class="mdi {{ $statusIcon }}"></i> {{ $statusText }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Kantor Regional Statistics Table (DI BAWAH TABLE INSTANSI) -->
                                    @if(isset($comparisonData['kanreg_stats']) && count($comparisonData['kanreg_stats']) > 0)
                                    <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">
                                                <i class="mdi mdi-office-building text-primary"></i> Statistik Per Kantor Regional
                                            </h5>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('dashboard.monev.export-comparison-kanreg-excel', ['previous_date' => $comparisonData['previous_period'], 'current_date' => $comparisonData['current_period']]) }}"
                                                   class="btn btn-sm btn-success">
                                                    <i class="mdi mdi-file-excel"></i> Excel
                                                </a>
                                                <a href="{{ route('dashboard.monev.export-comparison-kanreg-pdf', ['previous_date' => $comparisonData['previous_period'], 'current_date' => $comparisonData['current_period']]) }}"
                                                   class="btn btn-sm btn-danger"
                                                   target="_blank">
                                                    <i class="mdi mdi-file-pdf"></i> PDF
                                                </a>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th width="3%" class="text-center">#</th>
                                                        <th width="20%">Kantor Regional</th>
                                                        <th width="8%" class="text-center">Total Instansi</th>
                                                        <th width="10%" class="text-center">Skor Sebelumnya</th>
                                                        <th width="10%" class="text-center">Skor Sesudahnya</th>
                                                        <th width="8%" class="text-center">Naik</th>
                                                        <th width="8%" class="text-center">Stagnan</th>
                                                        <th width="8%" class="text-center">Turun</th>
                                                        <th width="10%" class="text-center">Rata-rata Perubahan</th>
                                                        <th width="10%" class="text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($comparisonData['kanreg_stats'] as $index => $kanreg)
                                                    <tr>
                                                        <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                                        <td><strong>{{ $kanreg->nama_kanreg }}</strong></td>
                                                        <td class="text-center">{{ $kanreg->total_instansi }}</td>
                                                        <td class="text-center">{{ number_format($kanreg->skor_sebelumnya, 2) }}</td>
                                                        <td class="text-center">{{ number_format($kanreg->skor_sesudahnya, 2) }}</td>
                                                        <td class="text-center">
                                                            <span class="badge badge-success">{{ $kanreg->naik }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-warning">{{ $kanreg->stagnan }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-danger">{{ $kanreg->turun }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            @if($kanreg->avg_perubahan > 0)
                                                                <span class="badge badge-success">
                                                                    <i class="mdi mdi-arrow-up"></i> +{{ number_format($kanreg->avg_perubahan, 2) }}
                                                                </span>
                                                            @elseif($kanreg->avg_perubahan < 0)
                                                                <span class="badge badge-danger">
                                                                    <i class="mdi mdi-arrow-down"></i> {{ number_format($kanreg->avg_perubahan, 2) }}
                                                                </span>
                                                            @else
                                                                <span class="badge badge-secondary">0.00</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            @if($kanreg->status == 'Naik')
                                                                <span class="badge badge-success">
                                                                    <i class="mdi mdi-trending-up"></i> Naik
                                                                </span>
                                                            @elseif($kanreg->status == 'Turun')
                                                                <span class="badge badge-danger">
                                                                    <i class="mdi mdi-trending-down"></i> Turun
                                                                </span>
                                                            @else
                                                                <span class="badge badge-secondary">
                                                                    <i class="mdi mdi-minus"></i> Stagnan
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    </div>

                    <!-- SECTION 2: DATA MONITORING & EVALUASI -->
                    @if($monevUploads->count() > 0)
                    <div class="row mb-3 mt-5">
                        <div class="col-12">
                            <div class="alert alert-success border-0 mb-3" style="background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%); border-left: 5px solid #4CAF50 !important;">
                                <h4 class="mb-0 fw-bold text-success">
                                    <i class="mdi mdi-numeric-2-circle"></i> SECTION 2: DATA MONITORING & EVALUASI
                                </h4>
                                <small class="text-muted">Lihat dan filter data monitoring DMS berdasarkan periode tertentu</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-success" style="border-width: 2px !important; box-shadow: 0 4px 8px rgba(76, 175, 80, 0.2);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <label class="mb-0 me-3 fw-bold">
                                                <i class="mdi mdi-filter-variant"></i> Filter Tanggal Data:
                                            </label>
                                            <select id="monev_date_filter" class="form-select me-3" style="width: 250px;">
                                                <option value="">-- Pilih Tanggal --</option>
                                                @foreach($monevUploads as $upload)
                                                    <option value="{{ $upload->upload_date }}"
                                                        {{ ($selectedMonevDate ?? '') == $upload->upload_date || (!$selectedMonevDate && $loop->first) ? 'selected' : '' }}>
                                                        {{ \Carbon\Carbon::parse($upload->upload_date)->format('d M Y') }}
                                                        @if($loop->first && !$selectedMonevDate)
                                                            (Terbaru)
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" id="reset_filter_btn" class="btn btn-secondary btn-sm me-2" style="display: {{ $selectedMonevDate ? 'inline-block' : 'none' }};">
                                                <i class="mdi mdi-reload"></i> Reset
                                            </button>
                                        </div>

                                        <!-- Print PDF Button -->
                                        @if($monevNasionalScore)
                                        <a href="{{ route('dashboard.monev.export-pdf') }}?monev_date={{ $selectedMonevDate ?? '' }}"
                                           id="export_pdf_btn"
                                           class="btn btn-danger btn-sm"
                                           target="_blank">
                                            <i class="mdi mdi-file-pdf"></i> Cetak PDF
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif


                    <!-- Summary Statistics Cards (FROM MONEV DATA) -->
                    <div class="row">
                        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-9">
                                            <div class="d-flex align-items-center align-self-start">
                                                <h3 class="mb-0">{{ $monevUploads->count() }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="icon icon-box-primary">
                                                <span class="mdi mdi-cloud-upload icon-item"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="text-muted font-weight-normal">Total Upload Monev</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-9">
                                            <div class="d-flex align-items-center align-self-start">
                                                <h3 class="mb-0">{{ $monevNasionalScore ? number_format($monevNasionalScore->total_instansi) : '0' }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="icon icon-box-success">
                                                <span class="mdi mdi-office-building icon-item"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="text-muted font-weight-normal">Total Instansi</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-9">
                                            <div class="d-flex align-items-center align-self-start">
                                                <h3 class="mb-0">{{ $monevNasionalScore ? number_format($monevNasionalScore->monev_skor_nasional, 1) : '0' }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="icon icon-box-info">
                                                <span class="mdi mdi-chart-line icon-item"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="text-muted font-weight-normal">Rata-rata Skor Nasional</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-9">
                                            <div class="d-flex align-items-center align-self-start">
                                                <h3 class="mb-0">{{ $monevNasionalScore ? \Carbon\Carbon::parse($monevNasionalScore->upload_date)->format('d M') : '-' }}</h3>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="icon icon-box-warning">
                                                <span class="mdi mdi-calendar icon-item"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="text-muted font-weight-normal">Data Terakhir</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- National Statistics Card (FROM MONEV DATA) -->
                    @if($monevNasionalScore)
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="card-title card-title-dash mb-0">
                                            <i class="mdi mdi-flag-checkered text-primary"></i> Monitoring Skor Nasional
                                        </h4>
                                        <small class="text-muted">
                                            <i class="mdi mdi-calendar"></i>
                                            Data per: {{ \Carbon\Carbon::parse($monevNasionalScore->upload_date)->format('d M Y') }}
                                        </small>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <div class="p-3 bg-gradient-primary text-white rounded">
                                                <p class="mb-1 opacity-75">Rata-rata Skor Nasional</p>
                                                <h2 class="mb-0 fw-bold">{{ number_format($monevNasionalScore->monev_skor_nasional, 2) }}</h2>
                                                <small class="opacity-75">Dari {{ number_format($monevNasionalScore->total_instansi) }} instansi</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <div class="p-3 bg-success text-white rounded">
                                                <p class="mb-1 opacity-75 small">Sangat Lengkap</p>
                                                <h2 class="mb-0 fw-bold">{{ number_format($monevNasionalScore->count_sangat_lengkap) }}</h2>
                                                <small class="opacity-75">> 90</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <div class="p-3 bg-primary text-white rounded">
                                                <p class="mb-1 opacity-75 small">Lengkap</p>
                                                <h2 class="mb-0 fw-bold">{{ number_format($monevNasionalScore->count_lengkap) }}</h2>
                                                <small class="opacity-75">55.6 - 90</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <div class="p-3 bg-warning text-white rounded">
                                                <p class="mb-1 opacity-75 small">Cukup Lengkap</p>
                                                <h2 class="mb-0 fw-bold">{{ number_format($monevNasionalScore->count_cukup_lengkap) }}</h2>
                                                <small class="opacity-75">30 - 55.5</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <div class="p-3 bg-danger text-white rounded">
                                                <p class="mb-1 opacity-75 small">Kurang Lengkap</p>
                                                <h2 class="mb-0 fw-bold">{{ number_format($monevNasionalScore->count_kurang_lengkap) }}</h2>
                                                <small class="opacity-75">< 30</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="row">
                        <div class="col-12 grid-margin">
                            <div class="alert alert-warning" role="alert">
                                <h5><i class="mdi mdi-alert"></i> Belum Ada Data Skor Nasional</h5>
                                <p class="mb-0">Silakan upload data CSV terlebih dahulu pada tab <strong>Kelola Statistik Instansi</strong> untuk mulai monitoring skor.</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Distribusi Kelengkapan Nasional (FROM MONEV DATA) -->
                    @if($monevNasionalScore)
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-chart-pie text-info"></i> Distribusi Kelengkapan Instansi Nasional
                                    </h4>
                                    <div class="row mt-4">
                                        <div class="col-md-3 mb-3">
                                            <div class="border border-success rounded p-3 text-center">
                                                <div class="mb-2">
                                                    <i class="mdi mdi-star-circle text-success" style="font-size: 2rem;"></i>
                                                </div>
                                                <h3 class="text-success mb-1">{{ number_format($monevNasionalScore->count_sangat_lengkap) }}</h3>
                                                <p class="text-muted mb-1 small">Sangat Lengkap</p>
                                                <small class="text-muted">Skor > 90</small>
                                                @if($monevNasionalScore->total_instansi > 0)
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar bg-success" style="width: {{ ($monevNasionalScore->count_sangat_lengkap / $monevNasionalScore->total_instansi) * 100 }}%"></div>
                                                </div>
                                                <small class="text-muted">{{ number_format(($monevNasionalScore->count_sangat_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="border border-primary rounded p-3 text-center">
                                                <div class="mb-2">
                                                    <i class="mdi mdi-check-circle text-primary" style="font-size: 2rem;"></i>
                                                </div>
                                                <h3 class="text-primary mb-1">{{ number_format($monevNasionalScore->count_lengkap) }}</h3>
                                                <p class="text-muted mb-1 small">Lengkap</p>
                                                <small class="text-muted">Skor 55.6 - 90</small>
                                                @if($monevNasionalScore->total_instansi > 0)
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ ($monevNasionalScore->count_lengkap / $monevNasionalScore->total_instansi) * 100 }}%"></div>
                                                </div>
                                                <small class="text-muted">{{ number_format(($monevNasionalScore->count_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="border border-warning rounded p-3 text-center">
                                                <div class="mb-2">
                                                    <i class="mdi mdi-alert-circle text-warning" style="font-size: 2rem;"></i>
                                                </div>
                                                <h3 class="text-warning mb-1">{{ number_format($monevNasionalScore->count_cukup_lengkap) }}</h3>
                                                <p class="text-muted mb-1 small">Cukup Lengkap</p>
                                                <small class="text-muted">Skor 30 - 55.5</small>
                                                @if($monevNasionalScore->total_instansi > 0)
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar bg-warning" style="width: {{ ($monevNasionalScore->count_cukup_lengkap / $monevNasionalScore->total_instansi) * 100 }}%"></div>
                                                </div>
                                                <small class="text-muted">{{ number_format(($monevNasionalScore->count_cukup_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="border border-danger rounded p-3 text-center">
                                                <div class="mb-2">
                                                    <i class="mdi mdi-close-circle text-danger" style="font-size: 2rem;"></i>
                                                </div>
                                                <h3 class="text-danger mb-1">{{ number_format($monevNasionalScore->count_kurang_lengkap) }}</h3>
                                                <p class="text-muted mb-1 small">Kurang Lengkap</p>
                                                <small class="text-muted">Skor < 30</small>
                                                @if($monevNasionalScore->total_instansi > 0)
                                                <div class="progress mt-2" style="height: 5px;">
                                                    <div class="progress-bar bg-danger" style="width: {{ ($monevNasionalScore->count_kurang_lengkap / $monevNasionalScore->total_instansi) * 100 }}%"></div>
                                                </div>
                                                <small class="text-muted">{{ number_format(($monevNasionalScore->count_kurang_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Top & Bottom Instansi Summary -->
                    <div class="row">
                        <div class="col-lg-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-trophy text-warning"></i> Top 5 Instansi Terbaik
                                    </h4>
                                    <p class="card-description">Instansi dengan skor kelengkapan tertinggi</p>
                                    <div class="table-responsive mt-3">
                                        <table class="table table-borderless">
                                            <thead>
                                                <tr>
                                                    <th class="ps-0">#</th>
                                                    <th>Instansi</th>
                                                    <th class="text-end">Skor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($monevTopInstansi as $index => $inst)
                                                <tr>
                                                    <td class="ps-0 fw-bold text-primary">{{ $index + 1 }}</td>
                                                    <td class="text-muted">{{ Str::limit($inst->nama_instansi, 40) }}</td>
                                                    <td class="text-end">
                                                        <span class="badge badge-success px-3 py-2">
                                                            {{ number_format($inst->monev_skor_instansi, 2) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Belum ada data</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-alert-octagon text-danger"></i> Top 5 Instansi Perlu Perhatian
                                    </h4>
                                    <p class="card-description">Instansi dengan skor kelengkapan terendah</p>
                                    <div class="table-responsive mt-3">
                                        <table class="table table-borderless">
                                            <thead>
                                                <tr>
                                                    <th class="ps-0">#</th>
                                                    <th>Instansi</th>
                                                    <th class="text-end">Skor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($monevBottomInstansi as $index => $inst)
                                                <tr>
                                                    <td class="ps-0 fw-bold text-danger">{{ $index + 1 }}</td>
                                                    <td class="text-muted">{{ Str::limit($inst->nama_instansi, 40) }}</td>
                                                    <td class="text-end">
                                                        <span class="badge badge-danger px-3 py-2">
                                                            {{ number_format($inst->monev_skor_instansi, 2) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Belum ada data</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistik Kantor Regional -->
                    @if($monevKantorRegionalStats->count() > 0)
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h4 class="card-title mb-1">
                                                <i class="mdi mdi-map-marker-multiple text-info"></i> Statistik Per Kantor Regional
                                            </h4>
                                            <p class="text-muted small mb-0">Pengelompokan instansi berdasarkan Kantor Regional BKN</p>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge badge-info px-3 py-2">
                                                Total: {{ $monevKantorRegionalStats->count() }} Kantor Regional
                                            </span>
                                            @if($monevNasionalScore)
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('dashboard.monev.export-kanreg-summary-excel', ['monev_date' => $monevNasionalScore->upload_date]) }}"
                                                   class="btn btn-sm btn-success"
                                                   title="Download Excel">
                                                    <i class="mdi mdi-file-excel"></i> Excel
                                                </a>
                                                <a href="{{ route('dashboard.monev.export-kanreg-summary-pdf', ['monev_date' => $monevNasionalScore->upload_date]) }}"
                                                   class="btn btn-sm btn-danger"
                                                   target="_blank"
                                                   title="Download PDF">
                                                    <i class="mdi mdi-file-pdf"></i> PDF
                                                </a>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-3">
                                        <table class="table table-hover table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" width="50">#</th>
                                                    <th class="text-center" width="120">Kantor Regional</th>
                                                    <th class="text-center">Total Instansi</th>
                                                    <th class="text-center">Rata-rata Skor</th>
                                                    <th class="text-center" width="150">Status Kelengkapan</th>
                                                    <th class="text-center" width="100">Sangat Lengkap</th>
                                                    <th class="text-center" width="100">Lengkap</th>
                                                    <th class="text-center" width="100">Cukup Lengkap</th>
                                                    <th class="text-center" width="100">Kurang Lengkap</th>
                                                    <th class="text-center" width="150">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($monevKantorRegionalStats as $index => $kanreg)
                                                <tr>
                                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                                    <td class="text-center">
                                                        <span class="badge badge-primary px-3 py-2">
                                                            Kantor Regional {{ str_pad($kanreg->kantor_regional_id, 2, '0', STR_PAD_LEFT) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-info px-3 py-2">
                                                            {{ number_format($kanreg->total_instansi) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <strong class="text-primary" style="font-size: 1.1em;">
                                                            {{ number_format($kanreg->rata_rata_skor, 2) }}
                                                        </strong>
                                                    </td>
                                                    <td class="text-center">
                                                        @php
                                                            // Determine dominant status
                                                            $statusCounts = [
                                                                'Sangat Lengkap' => $kanreg->count_sangat_lengkap,
                                                                'Lengkap' => $kanreg->count_lengkap,
                                                                'Cukup Lengkap' => $kanreg->count_cukup_lengkap,
                                                                'Kurang Lengkap' => $kanreg->count_kurang_lengkap
                                                            ];
                                                            arsort($statusCounts);
                                                            $dominantStatus = array_key_first($statusCounts);

                                                            $badgeClass = match($dominantStatus) {
                                                                'Sangat Lengkap' => 'badge-success',
                                                                'Lengkap' => 'badge-primary',
                                                                'Cukup Lengkap' => 'badge-warning',
                                                                'Kurang Lengkap' => 'badge-danger',
                                                                default => 'badge-secondary'
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $badgeClass }} px-3 py-2">
                                                            {{ $dominantStatus }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-success px-2 py-1">
                                                            {{ number_format($kanreg->count_sangat_lengkap) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-primary px-2 py-1">
                                                            {{ number_format($kanreg->count_lengkap) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-warning px-2 py-1">
                                                            {{ number_format($kanreg->count_cukup_lengkap) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-danger px-2 py-1">
                                                            {{ number_format($kanreg->count_kurang_lengkap) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('dashboard.monev.export-kanreg-excel', ['kanreg_id' => $kanreg->kantor_regional_id, 'monev_date' => $monevNasionalScore->upload_date]) }}"
                                                               class="btn btn-sm btn-success"
                                                               title="Download Excel">
                                                                <i class="mdi mdi-file-excel"></i> Excel
                                                            </a>
                                                            <a href="{{ route('dashboard.monev.export-kanreg-pdf', ['kanreg_id' => $kanreg->kantor_regional_id, 'monev_date' => $monevNasionalScore->upload_date]) }}"
                                                               class="btn btn-sm btn-danger"
                                                               target="_blank"
                                                               title="Download PDF">
                                                                <i class="mdi mdi-file-pdf"></i> PDF
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-secondary">
                                                <tr>
                                                    <th colspan="2" class="text-end">TOTAL:</th>
                                                    <th class="text-center">
                                                        <span class="badge badge-dark px-3 py-2">
                                                            {{ number_format($monevKantorRegionalStats->sum('total_instansi')) }}
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <strong class="text-dark" style="font-size: 1.1em;">
                                                            {{ number_format($monevKantorRegionalStats->avg('rata_rata_skor'), 2) }}
                                                        </strong>
                                                    </th>
                                                    <th></th>
                                                    <th class="text-center">
                                                        <span class="badge badge-success px-2 py-1">
                                                            {{ number_format($monevKantorRegionalStats->sum('count_sangat_lengkap')) }}
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="badge badge-primary px-2 py-1">
                                                            {{ number_format($monevKantorRegionalStats->sum('count_lengkap')) }}
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="badge badge-warning px-2 py-1">
                                                            {{ number_format($monevKantorRegionalStats->sum('count_cukup_lengkap')) }}
                                                        </span>
                                                    </th>
                                                    <th class="text-center">
                                                        <span class="badge badge-danger px-2 py-1">
                                                            {{ number_format($monevKantorRegionalStats->sum('count_kurang_lengkap')) }}
                                                        </span>
                                                    </th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- All Instansi Table with Search and Pagination -->
                    @if($monevNasionalScore)
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h4 class="card-title mb-0">
                                                <i class="mdi mdi-format-list-bulleted text-primary"></i> Daftar Semua Instansi
                                            </h4>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge badge-info px-3 py-2">
                                                Total: {{ $monevAllInstansi->total() }} Instansi
                                            </span>
                                            @if($monevNasionalScore)
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('dashboard.monev.export-all-excel', ['monev_date' => $monevNasionalScore->upload_date]) }}"
                                                   class="btn btn-sm btn-success"
                                                   title="Download Excel">
                                                    <i class="mdi mdi-file-excel"></i> Excel
                                                </a>
                                                <a href="{{ route('dashboard.monev.export-all-pdf', ['monev_date' => $monevNasionalScore->upload_date]) }}"
                                                   class="btn btn-sm btn-danger"
                                                   target="_blank"
                                                   title="Download PDF">
                                                    <i class="mdi mdi-file-pdf"></i> PDF
                                                </a>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Search Form -->
                                    <form method="GET" action="{{ route('dashboard.dms') }}" class="mb-3" id="monevSearchForm">
                                        <input type="hidden" name="monev_date" value="{{ $selectedMonevDate }}">
                                        <div class="input-group">
                                            <span class="input-group-text bg-primary text-white">
                                                <i class="mdi mdi-magnify"></i>
                                            </span>
                                            <input type="text"
                                                   class="form-control"
                                                   name="monev_search"
                                                   id="monevSearchInput"
                                                   placeholder="Cari nama instansi..."
                                                   value="{{ $monevSearch ?? '' }}"
                                                   autocomplete="off">
                                            <button type="button" class="btn btn-secondary" id="monevResetBtn" style="display: {{ $monevSearch ? 'block' : 'none' }};">
                                                <i class="mdi mdi-close"></i> Reset
                                            </button>
                                        </div>
                                        <small class="text-muted">Ketik untuk mencari secara otomatis</small>
                                    </form>

                                    <!-- Loading Indicator -->
                                    <div id="monevTableLoading" style="display: none;" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Mencari data...</p>
                                    </div>

                                    <div id="monevTableContainer">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th width="5%" class="text-center">#</th>
                                                        <th width="55%">Nama Instansi</th>
                                                        <th width="15%" class="text-center">Skor</th>
                                                        <th width="25%" class="text-center">Status Kelengkapan</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="monevTableBody">
                                                    @forelse($monevAllInstansi as $index => $instansi)
                                                        @php
                                                            $badgeClass = match($instansi->monev_status_kelengkapan) {
                                                                'Sangat Lengkap' => 'badge-success',
                                                                'Lengkap' => 'badge-primary',
                                                                'Cukup Lengkap' => 'badge-warning',
                                                                'Kurang Lengkap' => 'badge-danger',
                                                                default => 'badge-secondary'
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td class="text-center fw-bold">{{ $monevAllInstansi->firstItem() + $index }}</td>
                                                            <td>
                                                                <strong>{{ $instansi->nama_instansi }}</strong>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge {{ $instansi->monev_skor_instansi > 90 ? 'badge-success' : ($instansi->monev_skor_instansi >= 55.6 ? 'badge-primary' : ($instansi->monev_skor_instansi >= 30 ? 'badge-warning' : 'badge-danger')) }} px-3 py-2">
                                                                    {{ number_format($instansi->monev_skor_instansi, 2) }}
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge {{ $badgeClass }} px-3 py-2">
                                                                    {{ $instansi->monev_status_kelengkapan }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted py-4">
                                                                @if($monevSearch)
                                                                    <i class="mdi mdi-magnify mdi-48px d-block mb-2"></i>
                                                                    Tidak ada data yang ditemukan untuk "<strong>{{ $monevSearch }}</strong>"
                                                                @else
                                                                    <i class="mdi mdi-database-remove mdi-48px d-block mb-2"></i>
                                                                    Belum ada data instansi
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <div id="monevPaginationContainer">
                                            @if($monevAllInstansi->hasPages())
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div class="text-muted small" id="monevPaginationInfo">
                                                    Menampilkan {{ $monevAllInstansi->firstItem() }} - {{ $monevAllInstansi->lastItem() }} dari {{ $monevAllInstansi->total() }} instansi
                                                </div>
                                                <div id="monevPaginationLinks">
                                                    {{ $monevAllInstansi->links('pagination::bootstrap-5') }}
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Latest Upload Info (FROM MONEV DATA) -->
                    @if($monevNasionalScore)
                    <div class="row">
                        <div class="col-lg-12 grid-margin stretch-card">
                            <div class="card bg-info-subtle">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="mdi mdi-information text-info"></i> Informasi Data Monev Terbaru
                                    </h4>
                                    <div class="row mt-3">
                                        <div class="col-md-3">
                                            <p class="text-muted mb-1 small">Tanggal Data</p>
                                            <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($monevNasionalScore->upload_date)->format('d M Y') }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="text-muted mb-1 small">Total Instansi</p>
                                            <p class="mb-0 fw-bold">{{ number_format($monevNasionalScore->total_instansi) }} instansi</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="text-muted mb-1 small">Rata-rata Skor Nasional</p>
                                            <p class="mb-0 fw-bold text-primary">{{ number_format($monevNasionalScore->monev_skor_nasional, 2) }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <p class="text-muted mb-1 small">Diupload Pada</p>
                                            <p class="mb-0 fw-bold">{{ $monevNasionalScore->created_at->format('d M Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>

                <!-- Tab: Detail Skor Perorang dan Instansi -->
                <div class="tab-pane fade" id="detail-skor" role="tabpanel" aria-labelledby="detail-skor">
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

                    <!-- ===== SECTION 1: MONEV SKOR INSTANSI ===== -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="card-title text-white mb-0">
                                        <i class="mdi mdi-chart-box"></i> Section 1: Monev Skor Instansi
                                    </h4>
                                    <small>Upload data skor instansi manual dari database server</small>
                                </div>
                                <div class="card-body">
                                    @if(session('success'))
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="mdi mdi-check-circle"></i> {{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    @if(session('error'))
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="mdi mdi-alert-circle"></i> {{ session('error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <form action="{{ route('monev-dms.upload-csv') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Pilih File CSV Monev</label>
                                                <input type="file" name="monev_csv_file" class="form-control" accept=".csv" required>
                                                @error('monev_csv_file')
                                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">
                                                    Format CSV: id_instansi, nama_instansi, skor_instansi<br>
                                                    <code>Example: 001,Kementerian Dalam Negeri,85.50</code>
                                                </small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">Tanggal Upload</label>
                                                <input type="date" name="upload_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                                @error('upload_date')
                                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">Pilih tanggal untuk data ini</small>
                                            </div>
                                            <div class="col-md-2 mb-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="mdi mdi-upload"></i> Upload Monev CSV
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <hr class="my-4">

                                    <h5 class="mb-3">
                                        <i class="mdi mdi-history"></i> Riwayat Upload Monev
                                    </h5>

                                    @if($monevUploads->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tanggal Upload</th>
                                                        <th class="text-center">Total Instansi</th>
                                                        <th class="text-center">Rata-rata Skor</th>
                                                        <th class="text-center">Sangat Lengkap</th>
                                                        <th class="text-center">Lengkap</th>
                                                        <th class="text-center">Cukup Lengkap</th>
                                                        <th class="text-center">Kurang Lengkap</th>
                                                        <th class="text-center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($monevUploads as $upload)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ \Carbon\Carbon::parse($upload->upload_date)->format('d M Y') }}</strong>
                                                            @if($loop->first)
                                                                <span class="badge bg-success ms-2">Latest</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-info px-3 py-2">{{ number_format($upload->total_instansi) }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <strong class="text-primary">{{ number_format($upload->monev_skor_nasional, 2) }}</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-success">{{ number_format($upload->count_sangat_lengkap) }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-primary">{{ number_format($upload->count_lengkap) }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-warning">{{ number_format($upload->count_cukup_lengkap) }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-danger">{{ number_format($upload->count_kurang_lengkap) }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <form action="{{ route('monev-dms.delete') }}" method="POST" class="d-inline"
                                                                  onsubmit="return confirm('Yakin ingin menghapus data monev untuk tanggal {{ \Carbon\Carbon::parse($upload->upload_date)->format('d M Y') }}?');">
                                                                @csrf
                                                                <input type="hidden" name="upload_date" value="{{ $upload->upload_date }}">
                                                                <button type="submit" class="btn btn-sm btn-danger">
                                                                    <i class="mdi mdi-delete"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="mdi mdi-information"></i> Belum ada data monev yang diupload.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== SECTION 2: DETAIL SKOR INSTANSI (Upload Data DMS PNS) ===== -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <h4 class="card-title text-white mb-0">
                                        <i class="mdi mdi-database"></i> Section 2: Detail Skor Instansi
                                    </h4>
                                    <small>Upload data detail PNS dari CSV untuk perhitungan otomatis</small>
                                </div>
                                <div class="card-body">
                                    <h4 class="card-title">Upload Data DMS PNS</h4>

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

    // Real-time AJAX Search for Monev Instansi
    const monevSearchInput = document.getElementById('monevSearchInput');
    const monevSearchForm = document.getElementById('monevSearchForm');
    const monevTableBody = document.getElementById('monevTableBody');
    const monevTableContainer = document.getElementById('monevTableContainer');
    const monevTableLoading = document.getElementById('monevTableLoading');
    const monevPaginationContainer = document.getElementById('monevPaginationContainer');
    const monevResetBtn = document.getElementById('monevResetBtn');

    if (monevSearchInput && monevSearchForm) {
        let searchTimeout;
        let currentPage = 1;

        // Function to perform AJAX search
        function performSearch(page = 1) {
            const searchValue = monevSearchInput.value;
            const uploadDate = '{{ $selectedMonevDate ?? ($monevNasionalScore->upload_date ?? "") }}';

            // Show loading
            monevTableContainer.style.opacity = '0.5';
            monevTableLoading.style.display = 'block';

            // Perform AJAX request
            fetch(`{{ route('api.monev-dms.search') }}?upload_date=${uploadDate}&search=${encodeURIComponent(searchValue)}&page=${page}`, {
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
                updatePagination(data.pagination, searchValue);

                // Hide loading
                monevTableContainer.style.opacity = '1';
                monevTableLoading.style.display = 'none';

                // Update reset button visibility
                if (monevResetBtn) {
                    monevResetBtn.style.display = searchValue ? 'block' : 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                monevTableContainer.style.opacity = '1';
                monevTableLoading.style.display = 'none';
            });
        }

        // Function to update table body
        function updateTableBody(data, searchValue) {
            if (data.length === 0) {
                monevTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            ${searchValue ?
                                `<i class="mdi mdi-magnify mdi-48px d-block mb-2"></i>
                                Tidak ada data yang ditemukan untuk "<strong>${searchValue}</strong>"` :
                                `<i class="mdi mdi-database-remove mdi-48px d-block mb-2"></i>
                                Belum ada data instansi`
                            }
                        </td>
                    </tr>
                `;
            } else {
                let rows = '';
                data.forEach(item => {
                    rows += `
                        <tr>
                            <td class="text-center fw-bold">${item.no}</td>
                            <td class="text-center">${item.id_instansi}</td>
                            <td><strong>${item.nama_instansi}</strong></td>
                            <td class="text-center">
                                <span class="badge ${item.skor_badge_class} px-3 py-2">
                                    ${item.monev_skor_instansi}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge ${item.badge_class} px-3 py-2">
                                    ${item.monev_status_kelengkapan}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                monevTableBody.innerHTML = rows;
            }
        }

        // Function to update pagination
        function updatePagination(pagination, searchValue) {
            if (pagination.last_page <= 1) {
                monevPaginationContainer.innerHTML = '';
                return;
            }

            let paginationHTML = `
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan ${pagination.from || 0} - ${pagination.to || 0} dari ${pagination.total} instansi
                    </div>
                    <div>
                        <nav>
                            <ul class="pagination mb-0">
            `;

            // Previous button
            if (pagination.current_page > 1) {
                paginationHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="monevSearchPage(${pagination.current_page - 1}); return false;">Previous</a>
                    </li>
                `;
            }

            // Page numbers
            for (let i = 1; i <= pagination.last_page; i++) {
                if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                    paginationHTML += `
                        <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="monevSearchPage(${i}); return false;">${i}</a>
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
                        <a class="page-link" href="#" onclick="monevSearchPage(${pagination.current_page + 1}); return false;">Next</a>
                    </li>
                `;
            }

            paginationHTML += `
                            </ul>
                        </nav>
                    </div>
                </div>
            `;

            monevPaginationContainer.innerHTML = paginationHTML;
        }

        // Expose search function to window for pagination clicks
        window.monevSearchPage = function(page) {
            performSearch(page);
        };

        // Debounced search on input
        monevSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performSearch(1);
            }, 500);
        });

        // Enter key support
        monevSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                performSearch(1);
            }
        });

        // Reset button
        if (monevResetBtn) {
            monevResetBtn.addEventListener('click', function() {
                monevSearchInput.value = '';
                performSearch(1);
            });
        }
    }

    // Period Comparison AJAX
    const btnCompare = document.getElementById('btnCompare');
    const comparisonContainer = document.getElementById('comparisonContainer');
    const resetComparisonBtn = document.getElementById('resetComparisonBtn');

    if (btnCompare) {
        btnCompare.addEventListener('click', function() {
            const periodStart = document.getElementById('compare_period_start').value;
            const periodEnd = document.getElementById('compare_period_end').value;

            if (!periodStart || !periodEnd) {
                alert('Pilih kedua periode untuk dibandingkan');
                return;
            }

            // Show loading
            btnCompare.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Memproses...';
            btnCompare.disabled = true;

            // AJAX request
            fetch(`{{ route('dashboard.monev.compare') }}?compare_period_start=${periodStart}&compare_period_end=${periodEnd}`)
                .then(response => response.json())
                .then(data => {
                    // Update comparison section
                    updateComparisonSection(data);
                    // Show container and reset button
                    comparisonContainer.style.display = 'block';
                    resetComparisonBtn.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data perbandingan');
                })
                .finally(() => {
                    btnCompare.innerHTML = '<i class="mdi mdi-magnify"></i> Bandingkan';
                    btnCompare.disabled = false;
                });
        });
    }

    function resetComparison() {
        comparisonContainer.style.display = 'none';
        resetComparisonBtn.style.display = 'none';
        document.getElementById('compare_period_start').value = '';
        document.getElementById('compare_period_end').selectedIndex = 0;
    }

    // Global variables for pagination
    let currentComparisonData = null;
    let currentPage = 1;
    const itemsPerPage = 20;

    function updateComparisonSection(data) {
        currentComparisonData = data; // Store data globally for pagination
        currentPage = 1; // Reset to first page
        renderComparisonTable();
    }

    function renderComparisonTable() {
        if (!currentComparisonData) return;

        const data = currentComparisonData;
        const formatDate = (dateStr) => {
            const date = new Date(dateStr);
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        };

        const formatNumber = (num, decimals = 2) => {
            return parseFloat(num).toFixed(decimals);
        };

        const getBadgeClass = (perubahan) => {
            if (perubahan > 0) return 'badge-success';
            if (perubahan < 0) return 'badge-danger';
            return 'badge-warning';
        };

        const getIcon = (perubahan) => {
            if (perubahan > 0) return 'mdi-arrow-up';
            if (perubahan < 0) return 'mdi-arrow-down';
            return 'mdi-minus';
        };

        const getSign = (perubahan) => {
            if (perubahan > 0) return '+';
            return '';
        };

        // Get export URLs
        const exportExcelUrl = `{{ route('dashboard.monev.export-comparison-excel') }}?previous_date=${data.previous_period}&current_date=${data.current_period}`;
        const exportPdfUrl = `{{ route('dashboard.monev.export-comparison-pdf') }}?previous_date=${data.previous_period}&current_date=${data.current_period}`;

        // Build HTML
        let html = `
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-rounded">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4 class="card-title mb-1">
                                    <i class="mdi mdi-chart-timeline-variant text-info"></i> Analisis Perbandingan Periode
                                </h4>
                                <small class="text-muted">
                                    Periode ${formatDate(data.previous_period)} vs ${formatDate(data.current_period)}
                                </small>
                            </div>
                            <div class="btn-group" role="group">
                                <a href="${exportExcelUrl}" class="btn btn-sm btn-success">
                                    <i class="mdi mdi-file-excel"></i> Excel
                                </a>
                                <a href="${exportPdfUrl}" class="btn btn-sm btn-danger" target="_blank">
                                    <i class="mdi mdi-file-pdf"></i> PDF
                                </a>
                            </div>
                        </div>

                        <!-- Summary Statistics -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="alert alert-success mb-0 text-center">
                                    <h4 class="mb-1">${data.count_naik}</h4>
                                    <small>Instansi Naik</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-warning mb-0 text-center">
                                    <h4 class="mb-1">${data.count_stagnan}</h4>
                                    <small>Instansi Stagnan</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-danger mb-0 text-center">
                                    <h4 class="mb-1">${data.count_turun}</h4>
                                    <small>Instansi Turun</small>
                                </div>
                            </div>
                        </div>

                        <!-- Comparison Table -->
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40" class="text-center">#</th>
                                        <th>Nama Instansi</th>
                                        <th width="100" class="text-center">${formatDate(data.previous_period)}</th>
                                        <th width="100" class="text-center">${formatDate(data.current_period)}</th>
                                        <th width="100" class="text-center">Perubahan</th>
                                        <th width="100" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>`;

        // Sort all comparisons by perubahan desc
        const allComparisons = (data.all_comparisons || []).sort((a, b) => b.perubahan - a.perubahan);

        // Calculate pagination
        const totalItems = allComparisons.length;
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
        const paginatedData = allComparisons.slice(startIndex, endIndex);

        paginatedData.forEach((change, index) => {
            const globalIndex = startIndex + index + 1;
            const namaInstansi = change.nama_instansi.length > 50 ? change.nama_instansi.substring(0, 50) + '...' : change.nama_instansi;

            // Determine status - Stagnan hanya jika benar-benar 0
            let statusBadge = 'badge-warning';
            let statusText = 'Stagnan';
            let statusIcon = 'mdi-minus';

            if (change.perubahan > 0) {
                statusBadge = 'badge-success';
                statusText = 'Naik';
                statusIcon = 'mdi-trending-up';
            } else if (change.perubahan < 0) {
                statusBadge = 'badge-danger';
                statusText = 'Turun';
                statusIcon = 'mdi-trending-down';
            }

            html += `
                                    <tr>
                                        <td class="text-center fw-bold">${globalIndex}</td>
                                        <td>${namaInstansi}</td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">${formatNumber(change.skor_sebelum, 2)}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-info">${formatNumber(change.skor_sekarang, 2)}</span>
                                        </td>
                                        <td class="text-center">`;

            if (change.perubahan > 0) {
                html += `<span class="badge badge-success">
                            <i class="mdi mdi-arrow-up"></i> +${formatNumber(change.perubahan, 2)}
                         </span>`;
            } else if (change.perubahan < 0) {
                html += `<span class="badge badge-danger">
                            <i class="mdi mdi-arrow-down"></i> ${formatNumber(change.perubahan, 2)}
                         </span>`;
            } else {
                html += `<span class="badge badge-secondary">0.00</span>`;
            }

            html += `</td>
                                        <td class="text-center">
                                            <span class="badge ${statusBadge}">
                                                <i class="mdi ${statusIcon}"></i> ${statusText}
                                            </span>
                                        </td>
                                    </tr>`;
        });

        html += `
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Menampilkan ${startIndex + 1} - ${endIndex} dari ${totalItems} instansi
                            </div>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">`;

        // Previous button
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="changeComparisonPage(${currentPage - 1})">
                        <i class="mdi mdi-chevron-left"></i>
                    </a>
                </li>`;

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="changeComparisonPage(1)">1</a></li>`;
            if (startPage > 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="javascript:void(0)" onclick="changeComparisonPage(${i})">${i}</a>
                    </li>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="changeComparisonPage(${totalPages})">${totalPages}</a></li>`;
        }

        // Next button
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="changeComparisonPage(${currentPage + 1})">
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                </li>`;

        html += `
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;

        // Kantor Regional Statistics Table (DI BAWAH TABLE INSTANSI)
        if (data.kanreg_stats && data.kanreg_stats.length > 0) {
            html += `
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">
                    <i class="mdi mdi-office-building text-primary"></i> Statistik Per Kantor Regional
                </h5>
                <div class="btn-group" role="group">
                    <a href="{{ route('dashboard.monev.export-comparison-kanreg-excel') }}?previous_date=${data.previous_period}&current_date=${data.current_period}" class="btn btn-sm btn-success">
                        <i class="mdi mdi-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('dashboard.monev.export-comparison-kanreg-pdf') }}?previous_date=${data.previous_period}&current_date=${data.current_period}" class="btn btn-sm btn-danger" target="_blank">
                        <i class="mdi mdi-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-primary">
                        <tr>
                            <th width="3%" class="text-center">#</th>
                            <th width="20%">Kantor Regional</th>
                            <th width="8%" class="text-center">Total Instansi</th>
                            <th width="10%" class="text-center">Skor Sebelumnya</th>
                            <th width="10%" class="text-center">Skor Sesudahnya</th>
                            <th width="8%" class="text-center">Naik</th>
                            <th width="8%" class="text-center">Stagnan</th>
                            <th width="8%" class="text-center">Turun</th>
                            <th width="10%" class="text-center">Rata-rata Perubahan</th>
                            <th width="10%" class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>`;

            data.kanreg_stats.forEach((kanreg, index) => {
                let avgPerubahanBadge;
                if (kanreg.avg_perubahan > 0) {
                    avgPerubahanBadge = `<span class="badge badge-success"><i class="mdi mdi-arrow-up"></i> +${parseFloat(kanreg.avg_perubahan).toFixed(2)}</span>`;
                } else if (kanreg.avg_perubahan < 0) {
                    avgPerubahanBadge = `<span class="badge badge-danger"><i class="mdi mdi-arrow-down"></i> ${parseFloat(kanreg.avg_perubahan).toFixed(2)}</span>`;
                } else {
                    avgPerubahanBadge = `<span class="badge badge-secondary">0.00</span>`;
                }

                let statusBadge;
                if (kanreg.status === 'Naik') {
                    statusBadge = `<span class="badge badge-success"><i class="mdi mdi-trending-up"></i> Naik</span>`;
                } else if (kanreg.status === 'Turun') {
                    statusBadge = `<span class="badge badge-danger"><i class="mdi mdi-trending-down"></i> Turun</span>`;
                } else {
                    statusBadge = `<span class="badge badge-secondary"><i class="mdi mdi-minus"></i> Stagnan</span>`;
                }

                html += `
                        <tr>
                            <td class="text-center fw-bold">${index + 1}</td>
                            <td><strong>${kanreg.nama_kanreg}</strong></td>
                            <td class="text-center">${kanreg.total_instansi}</td>
                            <td class="text-center">${parseFloat(kanreg.skor_sebelumnya).toFixed(2)}</td>
                            <td class="text-center">${parseFloat(kanreg.skor_sesudahnya).toFixed(2)}</td>
                            <td class="text-center"><span class="badge badge-success">${kanreg.naik}</span></td>
                            <td class="text-center"><span class="badge badge-warning">${kanreg.stagnan}</span></td>
                            <td class="text-center"><span class="badge badge-danger">${kanreg.turun}</span></td>
                            <td class="text-center">${avgPerubahanBadge}</td>
                            <td class="text-center">${statusBadge}</td>
                        </tr>`;
            });

            html += `
                    </tbody>
                </table>
            </div>
        </div>`;
        }

        comparisonContainer.innerHTML = html;
    }

    function changeComparisonPage(page) {
        if (!currentComparisonData) return;
        const totalPages = Math.ceil((currentComparisonData.all_comparisons || []).length / itemsPerPage);
        if (page < 1 || page > totalPages) return;

        currentPage = page;
        renderComparisonTable();

        // Scroll to top of comparison section
        comparisonContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // AJAX Filter for Section 2 (Data Monitoring & Evaluasi)
    document.addEventListener('DOMContentLoaded', function() {
        const filterSelect = document.getElementById('monev_date_filter');
        const resetBtn = document.getElementById('reset_filter_btn');
        const exportPdfBtn = document.getElementById('export_pdf_btn');

        if (filterSelect) {
            filterSelect.addEventListener('change', function() {
                const selectedDate = this.value;
                loadMonevData(selectedDate);

                // Show/hide reset button
                if (resetBtn) {
                    resetBtn.style.display = selectedDate ? 'inline-block' : 'none';
                }

                // Update export PDF link
                if (exportPdfBtn) {
                    exportPdfBtn.href = "{{ route('dashboard.monev.export-pdf') }}?monev_date=" + selectedDate;
                }
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                filterSelect.value = '';
                filterSelect.dispatchEvent(new Event('change'));
            });
        }

        function loadMonevData(date) {
            // Show loading state
            showLoadingState();

            fetch("{{ route('dashboard.monev.filter') }}?monev_date=" + date)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateMonevContent(data);
                    } else {
                        alert('Gagal memuat data: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memuat data');
                })
                .finally(() => {
                    hideLoadingState();
                });
        }

        function showLoadingState() {
            // You can add a loading spinner here
            document.body.style.cursor = 'wait';
        }

        function hideLoadingState() {
            document.body.style.cursor = 'default';
        }

        function updateMonevContent(data) {
            // Update semua konten Section 2 akan ditambahkan di sini
            console.log('Data loaded:', data);

            // Untuk sementara reload page (nanti akan diupdate dengan dynamic content)
            window.location.href = "{{ route('dashboard.dms') }}?monev_date=" + data.selected_date;
        }
    });
</script>
@endpush

