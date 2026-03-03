@extends('layouts.app')

@section('title', 'Detail PIC DMS')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Detail PIC DMS</h4>
                        <p class="text-muted mb-0">Person In Charge Document Management System - Statistik Performa Tim</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('pic.exportPdf', $pic->id) }}?date_from={{ $dateFrom ?? '' }}&date_to={{ $dateTo ?? '' }}" class="btn btn-danger" target="_blank">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </a>
                        <a href="{{ route('pic.edit', $pic->id) }}" class="btn btn-warning">
                            <i class="mdi mdi-pencil"></i> Edit
                        </a>
                        <a href="{{ route('pic.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <!-- Info PIC -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Ketua PIC DMS:</th>
                                <td>
                                    @if($pic->ketua)
                                        <strong>{{ $pic->ketua->nama }}</strong> <span class="text-muted">(NIP: {{ $pic->ketua->nip }})</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($pic->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Dibuat:</th>
                                <td>{{ $pic->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Ringkasan</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Anggota Tim:</span>
                                    <strong>{{ $pic->anggota_count }} orang</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total Instansi:</span>
                                    <strong>{{ $pic->instansi_count }} instansi</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Anggota Tim -->
                <div class="mb-4">
                    <h5 class="mb-3">Anggota Tim ({{ $pic->anggota_count }})</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>NIP</th>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>Bergabung</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pic->anggota as $index => $anggota)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $anggota->nip }}</td>
                                        <td>{{ $anggota->nama }}</td>
                                        <td>
                                            @if($anggota->pivot->role == 'ketua')
                                                <span class="badge badge-primary">Ketua</span>
                                            @else
                                                <span class="badge badge-info">{{ ucfirst($anggota->pivot->role) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($anggota->pivot->assigned_at)->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Belum ada anggota tim</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Instansi yang Dipegang -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Instansi yang Dipegang ({{ $pic->instansi_count }})</h5>

                        <!-- Monev Date Filter -->
                        <div class="d-flex align-items-center gap-2">
                            <label for="monev_date" class="mb-0 text-muted small">Data Monev Skor:</label>
                            <select name="monev_date" id="monev_date" class="form-control form-control-sm" style="width: 200px;">
                                <option value="">-- Latest Data --</option>
                                @foreach($monevUploadDates as $upload)
                                    @php
                                        $uploadDateStr = $upload->upload_date instanceof \Carbon\Carbon ? $upload->upload_date->format('Y-m-d') : $upload->upload_date;
                                    @endphp
                                    <option value="{{ $uploadDateStr }}" {{ $monevDate == $uploadDateStr ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::parse($upload->upload_date)->format('d M Y') }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="spinner-border spinner-border-sm text-primary d-none" id="monevLoader" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama Instansi</th>
                                    <th>Monev Skor</th>
                                    <th>Status Kelengkapan</th>
                                    <th>Ditugaskan</th>
                                </tr>
                            </thead>
                            <tbody id="instansiTableBody">
                                @forelse($pic->instansi as $index => $inst)
                                    @php
                                        $monevScore = $monevScores[$inst->id] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $inst->nama }}</td>
                                        <td>
                                            @if($monevScore)
                                                <span class="badge
                                                    @if($monevScore->monev_skor_instansi > 90) badge-success
                                                    @elseif($monevScore->monev_skor_instansi >= 55.6) badge-primary
                                                    @elseif($monevScore->monev_skor_instansi >= 30) badge-warning
                                                    @else badge-danger
                                                    @endif
                                                ">
                                                    {{ number_format($monevScore->monev_skor_instansi, 2) }}
                                                </span>
                                            @else
                                                <span class="text-muted small">No data</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($monevScore)
                                                <span class="badge
                                                    @if($monevScore->monev_status_kelengkapan == 'Sangat Lengkap') badge-success
                                                    @elseif($monevScore->monev_status_kelengkapan == 'Lengkap') badge-primary
                                                    @elseif($monevScore->monev_status_kelengkapan == 'Cukup Lengkap') badge-warning
                                                    @else badge-danger
                                                    @endif
                                                ">
                                                    {{ $monevScore->monev_status_kelengkapan }}
                                                </span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($inst->pivot->assigned_at)->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Belum ada instansi yang ditugaskan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Chart: Monev Score Comparison Across Periods -->
                @if(!empty($chartData))
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">
                                <i class="mdi mdi-chart-line text-primary"></i>
                                Perbandingan Skor Instansi Antar Periode
                            </h5>
                            <p class="text-muted small mb-4">Menampilkan seluruh instansi yang dikelola per periode upload</p>
                            <canvas id="monevScoreChart" style="max-height: 400px;"></canvas>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Filter Tanggal -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Filter Periode Log Aktivitas</h5>
                        <form method="GET" action="{{ route('pic.show', $pic->id) }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="date_from">Tanggal Mulai</label>
                                        <input type="date"
                                               name="date_from"
                                               id="date_from"
                                               class="form-control"
                                               value="{{ $dateFrom ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="date_to">Tanggal Selesai</label>
                                        <input type="date"
                                               name="date_to"
                                               id="date_to"
                                               class="form-control"
                                               value="{{ $dateTo ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="mdi mdi-filter"></i> Terapkan Filter
                                            </button>
                                            <a href="{{ route('pic.show', $pic->id) }}" class="btn btn-secondary">
                                                <i class="mdi mdi-refresh"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($dateFrom || $dateTo)
                            <div class="alert alert-info mt-2 mb-0">
                                <i class="mdi mdi-information"></i>
                                Menampilkan data
                                @if($dateFrom && $dateTo)
                                    dari <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</strong>
                                    sampai <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</strong>
                                @elseif($dateFrom)
                                    dari <strong>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</strong> hingga sekarang
                                @else
                                    sampai <strong>{{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</strong>
                                @endif
                            </div>
                            @endif
                        </form>
                    </div>
                </div>

                <!-- Statistik Performa Tim -->
                <div class="mb-4">
                    <h5 class="mb-3">Statistik Performa Tim</h5>
                    <div class="row">
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Total Aktivitas</h6>
                                    <h2 class="mb-0">{{ number_format($stats['total_aktivitas']) }}</h2>
                                    <small>Mapping + Inject + Lap. Kekurangan</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Total Mapping</h6>
                                    <h2 class="mb-0">{{ number_format($stats['total_mapping']) }}</h2>
                                    <small>Dokumen dimapping</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Total Inject</h6>
                                    <h2 class="mb-0">{{ number_format($stats['total_inject']) }}</h2>
                                    <small>Dokumen diinject</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card text-white bg-danger">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Lap. Kekurangan Riwayat</h6>
                                    <h2 class="mb-0">{{ number_format($stats['total_laporan_kekurangan']) }}</h2>
                                    <small>Laporan kekurangan</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Unique PNS</h6>
                                    <h2 class="mb-0">{{ number_format($stats['unique_pns']) }}</h2>
                                    <small>PNS unik diproses</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performa Anggota -->
                <div>
                    <h5 class="mb-3">Performa Individual Anggota</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama</th>
                                    <th class="text-end">Total Aktivitas</th>
                                    <th class="text-end">Mapping</th>
                                    <th class="text-end">Inject</th>
                                    <th class="text-end">Lap. Kekurangan</th>
                                    <th class="text-end">Unique PNS</th>
                                    <th class="text-center">Kontribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalTeamActivities = collect($performaAnggota)->sum('total_aktivitas') ?: 1;
                                @endphp
                                @forelse($performaAnggota as $index => $performa)
                                    @php
                                        $kontribusiPersen = ($performa->total_aktivitas / $totalTeamActivities) * 100;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $performa->nama }}</strong>
                                            <br><small class="text-muted">{{ $performa->nip }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($performa->total_aktivitas) }}</td>
                                        <td class="text-end">{{ number_format($performa->total_mapping) }}</td>
                                        <td class="text-end">{{ number_format($performa->total_inject) }}</td>
                                        <td class="text-end">{{ number_format($performa->total_laporan_kekurangan) }}</td>
                                        <td class="text-end">{{ number_format($performa->unique_pns) }}</td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar"
                                                     role="progressbar"
                                                     style="width: {{ $kontribusiPersen }}%;"
                                                     aria-valuenow="{{ $kontribusiPersen }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($kontribusiPersen, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-3">Belum ada data performa</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // AJAX filter for monev date
    const monevDateSelect = document.getElementById('monev_date');
    const monevLoader = document.getElementById('monevLoader');
    const instansiTableBody = document.getElementById('instansiTableBody');
    const picInstansi = @json($pic->instansi);

    monevDateSelect.addEventListener('change', function() {
        const selectedDate = this.value;

        // Show loader
        monevLoader.classList.remove('d-none');
        instansiTableBody.style.opacity = '0.5';

        // Build URL
        let url = '{{ route("pic.show", $pic->id) }}';
        const params = new URLSearchParams();
        if (selectedDate) params.append('monev_date', selectedDate);
        @if($dateFrom)
            params.append('date_from', '{{ $dateFrom }}');
        @endif
        @if($dateTo)
            params.append('date_to', '{{ $dateTo }}');
        @endif

        // Fetch data
        fetch(url + '?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse HTML and extract table body
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTableBody = doc.getElementById('instansiTableBody');

            if (newTableBody) {
                instansiTableBody.innerHTML = newTableBody.innerHTML;
            }

            // Hide loader
            monevLoader.classList.add('d-none');
            instansiTableBody.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error loading monev data:', error);
            monevLoader.classList.add('d-none');
            instansiTableBody.style.opacity = '1';
        });
    });

    @if(!empty($chartData))
    // Prepare chart data
    const chartData = @json($chartData);

    // Extract all unique instansi names across all periods
    const instansiSet = new Set();
    // Sort periods: oldest to newest (left to right)
    const periods = Object.keys(chartData).sort((a, b) => new Date(a) - new Date(b));

    periods.forEach(period => {
        chartData[period].forEach(item => {
            instansiSet.add(item.nama_instansi);
        });
    });

    const allInstansi = Array.from(instansiSet);

    // Build datasets - one dataset per instansi
    const datasets = allInstansi.map((instansiNama, index) => {
        const scores = periods.map(period => {
            const found = chartData[period].find(item => item.nama_instansi === instansiNama);
            return found ? parseFloat(found.monev_skor_instansi) : 0;
        });

        // Generate color
        const colors = [
            'rgba(54, 162, 235, 0.8)',  // Blue
            'rgba(75, 192, 192, 0.8)',  // Green
            'rgba(255, 206, 86, 0.8)',  // Yellow
            'rgba(153, 102, 255, 0.8)', // Purple
            'rgba(255, 159, 64, 0.8)',  // Orange
            'rgba(255, 99, 132, 0.8)',  // Red
            'rgba(201, 203, 207, 0.8)', // Grey
            'rgba(83, 211, 87, 0.8)',   // Light Green
            'rgba(237, 137, 204, 0.8)', // Pink
            'rgba(131, 197, 255, 0.8)', // Light Blue
        ];

        return {
            label: instansiNama.length > 30 ? instansiNama.substring(0, 30) + '...' : instansiNama,
            data: scores,
            backgroundColor: colors[index % colors.length],
            borderColor: colors[index % colors.length].replace('0.8', '1'),
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: false
        };
    });

    // Format period labels
    const labels = periods.map(period => {
        const date = new Date(period);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    });

    // Create chart
    const ctx = document.getElementById('monevScoreChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: {
                            size: 11
                        }
                    }
                },
                title: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toFixed(2);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(0);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Skor Instansi'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Periode Upload'
                    }
                }
            }
        }
    });
    @endif
});
</script>
@endpush
