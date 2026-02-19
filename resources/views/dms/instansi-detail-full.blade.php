@extends('layouts.app')

@section('title', 'Detail Instansi - ' . $instansiInfo->instansi_nama)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-2">{{ $instansiInfo->instansi_nama }}</h4>
                        <p class="text-muted mb-0">Riwayat Skor dan Detail PNS</p>
                    </div>
                    <a href="{{ route('dms.instansi.all') }}" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card card-tale">
                    <div class="card-body">
                        <p class="mb-2">Total PNS</p>
                        <h3 class="mb-0">{{ number_format($instansiInfo->total_pns) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dark-blue">
                    <div class="card-body">
                        <p class="mb-2">Skor System</p>
                        <h3 class="mb-0">{{ number_format($instansiInfo->skor_instansi_calculated_system, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-light-blue">
                    <div class="card-body">
                        <p class="mb-2">Skor CSV</p>
                        <h3 class="mb-0">{{ number_format($instansiInfo->skor_instansi_calculated_csv, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card
                    @if($instansiInfo->status_kelengkapan == 'Sangat Lengkap') card-light-danger
                    @elseif($instansiInfo->status_kelengkapan == 'Lengkap') bg-gradient-primary
                    @elseif($instansiInfo->status_kelengkapan == 'Cukup Lengkap') bg-gradient-warning
                    @else bg-gradient-danger
                    @endif">
                    <div class="card-body text-white">
                        <p class="mb-2">Status</p>
                        <h5 class="mb-0">{{ $instansiInfo->status_kelengkapan }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart - Distribusi Kategori Skor -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Distribusi Kategori Kelengkapan</h4>
                        <p class="text-muted">Persentase PNS berdasarkan kategori kelengkapan</p>
                        <div class="chartjs-wrapper mt-4">
                            <canvas id="scoreDistributionPieChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Detail Distribusi</h4>
                        <p class="text-muted mb-4">Jumlah dan persentase per kategori</p>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-center">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalPns = $instansiInfo->total_pns;
                                        $categories = [
                                            'Sangat Lengkap' => ['color' => 'success', 'icon' => 'mdi-check-circle'],
                                            'Lengkap' => ['color' => 'primary', 'icon' => 'mdi-check'],
                                            'Cukup Lengkap' => ['color' => 'warning', 'icon' => 'mdi-alert'],
                                            'Kurang Lengkap' => ['color' => 'danger', 'icon' => 'mdi-close-circle']
                                        ];
                                    @endphp
                                    @foreach($categories as $category => $style)
                                        @php
                                            $jumlah = $scoreDistribution->get($category)->jumlah ?? 0;
                                            $persentase = $totalPns > 0 ? ($jumlah / $totalPns) * 100 : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <i class="mdi {{ $style['icon'] }} text-{{ $style['color'] }}"></i>
                                                <strong>{{ $category }}</strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-{{ $style['color'] }}">{{ number_format($jumlah) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <strong>{{ number_format($persentase, 1) }}%</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="table-active">
                                        <td><strong>Total</strong></td>
                                        <td class="text-center"><strong>{{ number_format($totalPns) }}</strong></td>
                                        <td class="text-center"><strong>100%</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Score History Chart -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Grafik Pergerakan Skor</h4>
                <p class="text-muted">Perbandingan skor dari waktu ke waktu</p>
                <div class="chartjs-wrapper mt-4">
                    <canvas id="scoreHistoryChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- History Table -->
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title">Riwayat Perhitungan</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal Upload</th>
                                <th>Tanggal Dihitung</th>
                                <th>Total PNS</th>
                                <th>Skor System</th>
                                <th>Skor CSV</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scoreHistory as $index => $history)
                                @php
                                    $kelengkapanBadge = match($history->status_kelengkapan) {
                                        'Sangat Lengkap' => 'bg-success',
                                        'Lengkap' => 'bg-primary',
                                        'Cukup Lengkap' => 'bg-warning',
                                        'Kurang Lengkap' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($history->upload_date)->format('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($history->calculated_at)->format('d M Y H:i') }}</td>
                                    <td>{{ number_format($history->total_pns) }}</td>
                                    <td><span class="badge bg-info">{{ number_format($history->skor_instansi_calculated_system, 2) }}</span></td>
                                    <td><span class="badge bg-secondary">{{ number_format($history->skor_instansi_calculated_csv, 2) }}</span></td>
                                    <td><span class="badge {{ $kelengkapanBadge }}">{{ $history->status_kelengkapan }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PNS List -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Daftar PNS (Data Terbaru)</h4>
                <p class="text-muted mb-4">Berdasarkan upload terakhir pada {{ \Carbon\Carbon::parse($scoreHistory->last()->upload_date)->format('d M Y') }}</p>

                @if($pnsList->isEmpty())
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i> Tidak ada data PNS untuk instansi ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIP</th>
                                    <th>Nama</th>
                                    <th>Status</th>
                                    <th>Skor CSV</th>
                                    <th>Skor System</th>
                                    <th>Status Kelengkapan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pnsList as $index => $pns)
                                    @php
                                        $kelengkapanBadge = match($pns->status_kelengkapan) {
                                            'Sangat Lengkap' => 'success',
                                            'Lengkap' => 'primary',
                                            'Cukup Lengkap' => 'warning',
                                            'Kurang Lengkap' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $pnsList->firstItem() + $index }}</td>
                                        <td>{{ $pns->nip }}</td>
                                        <td>{{ $pns->nama }}</td>
                                        <td>{{ $pns->status_cpns_pns }}</td>
                                        <td>{{ number_format($pns->skor_csv, 2) }}</td>
                                        <td>{{ number_format($pns->skor_calculated, 2) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $kelengkapanBadge }}">
                                                {{ $pns->status_kelengkapan }}
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick='showDetailPNS(@json($pns))'>
                                                <i class="mdi mdi-eye"></i> Lihat
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Tidak ada data PNS</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Menampilkan {{ $pnsList->firstItem() }} - {{ $pnsList->lastItem() }} dari {{ $pnsList->total() }} data
                        </div>
                        <div>
                            {{ $pnsList->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail PNS -->
<div class="modal fade" id="pnsDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title"><i class="mdi mdi-calculator"></i> Detail Perhitungan Skor Arsip</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="pnsDetailBody" style="max-height: 80vh; overflow-y: auto;">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.pagination {
    margin-bottom: 0;
}
.pagination .page-link {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-color: #dee2e6;
    color: #6c757d;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.pagination .page-link:hover {
    background-color: #e9ecef;
    color: #0d6efd;
}

/* Modal Detail Styling - sama seperti perhitungan_skor_arsip.html */
#pnsDetailModal .modal-body {
    background: #f8f9fa;
}
#pnsDetailModal .detail-section {
    margin-bottom: 25px;
}
#pnsDetailModal .detail-section h5 {
    color: #667eea;
    margin-bottom: 15px;
    font-size: 1.2em;
    font-weight: 600;
}
#pnsDetailModal .score-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
#pnsDetailModal .score-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
#pnsDetailModal .score-table thead th {
    color: white;
    font-weight: 600;
    border: none;
}
#pnsDetailModal .score-table tbody tr:hover {
    background: #f8f9fa;
}
#pnsDetailModal .kondisional-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    border-left: 4px solid #667eea;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
#pnsDetailModal .kondisional-card h6 {
    color: #667eea;
    font-weight: 600;
    margin-bottom: 10px;
}
#pnsDetailModal .item-badge {
    padding: 6px 10px;
    border-radius: 5px;
    font-size: 0.85em;
    display: inline-block;
    margin: 3px;
}
#pnsDetailModal .score-card-mini {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
#pnsDetailModal .score-card-mini h6 {
    font-size: 0.9em;
    opacity: 0.9;
    margin-bottom: 10px;
}
#pnsDetailModal .score-card-mini h2 {
    font-size: 2em;
    font-weight: bold;
    margin: 0;
}
#pnsDetailModal .info-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if ($("#scoreHistoryChart").length) {
        var ctx = document.getElementById('scoreHistoryChart').getContext('2d');

        // Create gradients
        var gradientStrokeBlue = ctx.createLinearGradient(0, 0, 0, 181);
        gradientStrokeBlue.addColorStop(0, 'rgba(75, 192, 192, 0.8)');
        gradientStrokeBlue.addColorStop(1, 'rgba(75, 192, 192, 0.2)');

        var gradientStrokePink = ctx.createLinearGradient(0, 0, 0, 181);
        gradientStrokePink.addColorStop(0, 'rgba(255, 99, 132, 0.8)');
        gradientStrokePink.addColorStop(1, 'rgba(255, 99, 132, 0.2)');

        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartData['labels']),
                datasets: [
                    {
                        label: 'Skor System',
                        data: @json($chartData['system_scores']),
                        backgroundColor: gradientStrokeBlue,
                        borderColor: 'rgb(75, 192, 192)',
                        borderWidth: 2,
                        fill: true,
                        pointBorderColor: "#fff",
                        pointBackgroundColor: "rgb(75, 192, 192)",
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4
                    },
                    {
                        label: 'Skor CSV',
                        data: @json($chartData['csv_scores']),
                        backgroundColor: gradientStrokePink,
                        borderColor: 'rgb(255, 99, 132)',
                        borderWidth: 2,
                        fill: true,
                        pointBorderColor: "#fff",
                        pointBackgroundColor: "rgb(255, 99, 132)",
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        border: {
                            display: false
                        },
                        grid: {
                            display: true,
                            color: "rgba(0, 0, 0, 0.05)"
                        },
                        ticks: {
                            color: "#9ca2a9",
                            callback: function(value) {
                                return value.toFixed(0);
                            }
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

    // Pie Chart - Score Distribution
    if ($("#scoreDistributionPieChart").length) {
        var ctxPie = document.getElementById('scoreDistributionPieChart').getContext('2d');

        @php
            $sangatLengkap = $scoreDistribution->get('Sangat Lengkap')->jumlah ?? 0;
            $lengkap = $scoreDistribution->get('Lengkap')->jumlah ?? 0;
            $cukupLengkap = $scoreDistribution->get('Cukup Lengkap')->jumlah ?? 0;
            $kurangLengkap = $scoreDistribution->get('Kurang Lengkap')->jumlah ?? 0;
            $total = $instansiInfo->total_pns;
        @endphp

        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Sangat Lengkap', 'Lengkap', 'Cukup Lengkap', 'Kurang Lengkap'],
                datasets: [{
                    data: [{{ $sangatLengkap }}, {{ $lengkap }}, {{ $cukupLengkap }}, {{ $kurangLengkap }}],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',   // success
                        'rgba(13, 110, 253, 0.8)',  // primary
                        'rgba(255, 193, 7, 0.8)',   // warning
                        'rgba(220, 53, 69, 0.8)'    // danger
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
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = {{ $total }};
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});

function showDetailPNS(pns) {
    // Parse status_arsip untuk mendapatkan detail dokumen
    const statusArsip = pns.status_arsip ? JSON.parse(pns.status_arsip) : null;

    if (!statusArsip) {
        alert('Data status arsip tidak tersedia');
        return;
    }

    // Hitung detail dari status_arsip (sama seperti di DmsScoreCalculator)
    const detail = hitungDetailSkor(statusArsip, pns.status_cpns_pns);

    const jenisNama = {
        'angka_kredit': 'Angka Kredit',
        'pindah_instansi': 'Pindah Instansi',
        'pmk': 'PMK (Peninjauan Masa Kerja)',
        'penghargaan': 'Penghargaan',
        'cltn': 'CLTN (Cuti Luar Tanggungan Negara)',
        'skp22': 'SKP/Kinerja'
    };

    let modalHTML = `
        <div class="detail-section">
            <div class="info-card">
                <div class="row">
                    <div class="col-md-4">
                        <strong>NIP:</strong> <code style="color: white;">${pns.nip}</code>
                    </div>
                    <div class="col-md-4">
                        <strong>Nama:</strong> ${pns.nama}
                    </div>
                    <div class="col-md-4">
                        <strong>Status:</strong>
                        <span class="badge ${pns.status_cpns_pns === 'P' ? 'badge-light' : 'badge-light'}">
                            ${pns.status_cpns_pns === 'P' ? 'PNS' : 'CPNS'}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h5><i class="mdi mdi-file-document"></i> Dokumen Arsip Utama (Maksimal 90 Poin)</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm score-table">
                    <thead>
                            <tr>
                                <th>Jenis Dokumen</th>
                                <th width="20%" class="text-center">Kondisi</th>
                                <th width="15%" class="text-end">Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>DRH</strong></td>
                                <td class="text-center"><span class="badge badge-success">Fixed</span></td>
                                <td class="text-end"><strong>${detail.drh ? detail.drh.toFixed(2) : '7.50'}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>D2NIP</strong></td>
                                <td class="text-center">
                                    ${detail.d2nip > 0 ? '<span class="badge badge-success">✓ Lengkap</span>' : '<span class="badge badge-danger">✗ Tidak Lengkap</span>'}
                                </td>
                                <td class="text-end"><strong>${detail.d2nip ? detail.d2nip.toFixed(2) : '0.00'}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>SK CPNS</strong></td>
                                <td class="text-center">
                                    ${detail.cpns > 0 ? '<span class="badge badge-success">✓ Lengkap</span>' : '<span class="badge badge-danger">✗ Tidak Lengkap</span>'}
                                </td>
                                <td class="text-end"><strong>${detail.cpns ? detail.cpns.toFixed(2) : '0.00'}</strong></td>
                            </tr>
                            <tr>
                                <td><strong>SK PNS</strong></td>
                                <td class="text-center">
                                    ${detail.pns > 0 ? '<span class="badge badge-success">✓ Lengkap</span>' :
                                      (pns.status_cpns_pns === 'C' ? '<span class="badge badge-info">CPNS - Auto 7.5</span>' : '<span class="badge badge-danger">✗ Tidak Lengkap</span>')}
                                </td>
                                <td class="text-end"><strong>${detail.pns ? detail.pns.toFixed(2) : '0.00'}</strong></td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Pendidikan</strong>
                                    ${detail.pendidikan && detail.pendidikan.items ? generateItemList(detail.pendidikan.items) : ''}
                                </td>
                                <td class="text-center">
                                    <span class="badge ${detail.pendidikan.skor >= 15 ? 'badge-success' : 'badge-warning'}">
                                        ${detail.pendidikan.lengkap}/${detail.pendidikan.total} Lengkap
                                    </span>
                                </td>
                                <td class="text-end"><strong>${detail.pendidikan.skor.toFixed(2)}</strong></td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Golongan</strong>
                                    ${detail.golongan && detail.golongan.items ? generateItemList(detail.golongan.items) : ''}
                                </td>
                                <td class="text-center">
                                    <span class="badge ${detail.golongan.skor >= 15 ? 'badge-success' : 'badge-warning'}">
                                        ${detail.golongan.lengkap}/${detail.golongan.total} Lengkap
                                    </span>
                                </td>
                                <td class="text-end"><strong>${detail.golongan.skor.toFixed(2)}</strong></td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Jabatan</strong>
                                    ${detail.jabatan && detail.jabatan.items ? generateItemList(detail.jabatan.items) : ''}
                                </td>
                                <td class="text-center">
                                    <span class="badge ${detail.jabatan.skor >= 15 ? 'badge-success' : 'badge-warning'}">
                                        ${detail.jabatan.lengkap}/${detail.jabatan.total} Lengkap
                                    </span>
                                </td>
                                <td class="text-end"><strong>${detail.jabatan.skor.toFixed(2)}</strong></td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>Diklat</strong>
                                    ${detail.diklat && detail.diklat.items ? generateItemList(detail.diklat.items) : ''}
                                </td>
                                <td class="text-center">
                                    <span class="badge ${detail.diklat.skor >= 15 ? 'badge-success' : 'badge-warning'}">
                                        ${detail.diklat.lengkap}/${detail.diklat.total} Lengkap
                                    </span>
                                </td>
                                <td class="text-end"><strong>${detail.diklat.skor.toFixed(2)}</strong></td>
                            </tr>
                            <tr class="table-info">
                                <td colspan="2" class="text-end"><strong>TOTAL SKOR ARSIP UTAMA:</strong></td>
                                <td class="text-end"><strong style="color: #0d6efd; font-size: 1.2em;">${detail.skorUtama ? detail.skorUtama.toFixed(2) : pns.skor_calculated.toFixed(2)}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h5><i class="mdi mdi-archive"></i> Riwayat Arsip Kondisional (Maksimal 10 Poin)</h5>
            ${detail.kondisional && detail.kondisional.total > 0 ? `
                <div class="info-card" style="background: linear-gradient(135deg, #4dabf7 0%, #339af0 100%);">
                    <strong>Total Riwayat:</strong> ${detail.kondisional.total} |
                    <strong>Lengkap:</strong> ${detail.kondisional.lengkap} |
                    <strong>Rumus:</strong> 10 × (${detail.kondisional.lengkap}/${detail.kondisional.total}) =
                    <strong style="font-size: 1.2em;">${detail.skorKondisional ? detail.skorKondisional.toFixed(2)  : '0.00'}</strong>
                </div>
                ${generateKondisionalItems(detail.kondisional.items, jenisNama)}
            ` : `
                <div class="info-card" style="background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%); text-align: center;">
                    <h5 style="margin: 0;"><i class="mdi mdi-trophy"></i> Tidak memiliki riwayat kondisional</h5>
                    <p class="mb-0" style="font-size: 1.3em; margin-top: 10px;"><strong>BONUS +10 poin</strong></p>
                </div>
            `}
        </div>

        <div class="detail-section">
            <h5><i class="mdi mdi-chart-bar"></i> Ringkasan Skor Final</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="score-card-mini">
                        <h6>Skor Arsip Utama</h6>
                        <h2>${detail.skorUtama ? detail.skorUtama.toFixed(2) : '0.00'}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="score-card-mini" style="background: linear-gradient(135deg, #4dabf7 0%, #339af0 100%);">
                        <h6>Skor Kondisional</h6>
                        <h2>${detail.skorKondisional ? detail.skorKondisional.toFixed(2) : '0.00'}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="score-card-mini" style="background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);">
                        <h6>Skor Final</h6>
                        <h2>${parseFloat(pns.skor_calculated).toFixed(2)}</h2>
                    </div>
                </div>
                    </div>
                </div>

                <table class="table table-sm mt-3">
                    <tr>
                        <td><strong>Skor Final (System):</strong></td>
                        <td class="text-end"><span class="badge badge-info" style="font-size: 1.1em;">${parseFloat(pns.skor_calculated).toFixed(2)}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Skor CSV:</strong></td>
                        <td class="text-end"><span class="badge badge-secondary" style="font-size: 1.1em;">${parseFloat(pns.skor_csv).toFixed(2)}</span></td>
                    </tr>
                    <tr class="${parseFloat(pns.skor_calculated) - parseFloat(pns.skor_csv) >= 0 ? 'table-success' : 'table-danger'}">
                        <td><strong>Selisih:</strong></td>
                        <td class="text-end">
                            <strong style="font-size: 1.2em;">
                                ${(parseFloat(pns.skor_calculated) - parseFloat(pns.skor_csv)).toFixed(2)}
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Kategori Kelengkapan:</strong></td>
                        <td class="text-end">
                            <span class="badge ${pns.status_kelengkapan === 'Sangat Lengkap' || pns.status_kelengkapan === 'Lengkap' ? 'badge-success' : 'badge-warning'}">
                                ${pns.status_kelengkapan}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    `;

    document.getElementById('pnsDetailBody').innerHTML = modalHTML;
    new bootstrap.Modal(document.getElementById('pnsDetailModal')).show();
}

function generateItemList(items) {
    if (!items || Object.keys(items).length === 0) return '';

    let html = '<div class="mt-2"><small>';
    for (const [key, value] of Object.entries(items)) {
        const badgeClass = value === 1 ? 'badge-success' : 'badge-danger';
        const icon = value === 1 ? '✓' : '✗';
        html += `<span class="badge ${badgeClass} me-1 mb-1">${icon} ${key}</span>`;
    }
    html += '</small></div>';
    return html;
}

function generateKondisionalItems(items, jenisNama) {
    if (!items) return '';

    let html = '';
    for (const [jenis, dokumen] of Object.entries(items)) {
        const totalItems = Object.keys(dokumen).length;
        const lengkapItems = Object.values(dokumen).filter(v => v === 1).length;

        html += `
            <div class="kondisional-card">
                <h6>
                    ${jenisNama[jenis] || jenis.toUpperCase()}
                    <span class="badge ${lengkapItems === totalItems ? 'badge-success' : 'badge-warning'}" style="float: right;">
                        ${lengkapItems}/${totalItems} Lengkap
                    </span>
                </h6>
                <div class="row mt-2">
                    ${Object.entries(dokumen).map(([key, value]) => `
                        <div class="col-md-6">
                            <span class="item-badge ${value === 1 ? 'bg-success' : 'bg-danger'} text-white">
                                ${value === 1 ? '✓' : '✗'} ${key}
                            </span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    return html;
}

function hitungDetailSkor(statusArsip, statusCPNS_PNS) {
    let skorUtama = 0;
    const detail = {
        drh: 7.5,
        d2nip: 0, cpns: 0, pns: 0,
        pendidikan: { lengkap: 0, total: 0, skor: 0, items: {} },
        golongan: { lengkap: 0, total: 0, skor: 0, items: {} },
        jabatan: { lengkap: 0, total: 0, skor: 0, items: {} },
        diklat: { lengkap: 0, total: 0, skor: 0, items: {} },
        kondisional: { lengkap: 0, total: 0, items: {} }
    };

    skorUtama += 7.5; // DRH fixed

    if (statusArsip.data_utama) {
        if (statusArsip.data_utama.d2np === 1) {
            detail.d2nip = 7.5;
            skorUtama += 7.5;
        }
        if (statusArsip.data_utama.cpns === 1 || statusArsip.data_utama.spmt_cpns === 1) {
            detail.cpns = 7.5;
            skorUtama += 7.5;
        }
        if (statusCPNS_PNS === 'C' || statusArsip.data_utama.pns === 1) {
            detail.pns = 7.5;
            skorUtama += 7.5;
        }
    }

    // Pendidikan
    if (statusArsip.pendidikan) {
        const total = Object.keys(statusArsip.pendidikan).length;
        const lengkap = Object.values(statusArsip.pendidikan).filter(v => v === 1).length;
        detail.pendidikan = {
            lengkap, total,
            skor: total === 0 ? 15 : (lengkap / total) * 15,
            items: statusArsip.pendidikan
        };
        skorUtama += detail.pendidikan.skor;
    } else {
        detail.pendidikan.skor = 15;
        skorUtama += 15;
    }

    // Golongan
    if (statusArsip.golongan) {
        const total = Object.keys(statusArsip.golongan).length;
        const lengkap = Object.values(statusArsip.golongan).filter(v => v === 1).length;
        detail.golongan = {
            lengkap, total,
            skor: total === 0 ? 15 : (lengkap / total) * 15,
            items: statusArsip.golongan
        };
        skorUtama += detail.golongan.skor;
    } else {
        detail.golongan.skor = 15;
        skorUtama += 15;
    }

    // Jabatan
    if (statusArsip.jabatan) {
        const total = Object.keys(statusArsip.jabatan).length;
        const lengkap = Object.values(statusArsip.jabatan).filter(v => v === 1).length;
        detail.jabatan = {
            lengkap, total,
            skor: total === 0 ? 15 : (lengkap / total) * 15,
            items: statusArsip.jabatan
        };
        skorUtama += detail.jabatan.skor;
    } else {
        detail.jabatan.skor = 15;
        skorUtama += 15;
    }

    // Diklat
    if (statusArsip.diklat) {
        const total = Object.keys(statusArsip.diklat).length;
        const lengkap = Object.values(statusArsip.diklat).filter(v => v === 1).length;
        detail.diklat = {
            lengkap, total,
            skor: total === 0 ? 15 : (lengkap / total) * 15,
            items: statusArsip.diklat
        };
        skorUtama += detail.diklat.skor;
    } else {
        detail.diklat.skor = 15;
        skorUtama += 15;
    }

    // ARSIP KONDISIONAL
    let totalRiwayat = 0;
    let totalLengkap = 0;
    const jenisKondisional = ['angka_kredit', 'pindah_instansi', 'pmk', 'penghargaan', 'cltn', 'skp22'];

    jenisKondisional.forEach(jenis => {
        if (statusArsip[jenis]) {
            const items = statusArsip[jenis];
            const total = Object.keys(items).length;
            const lengkap = Object.values(items).filter(v => v === 1).length;
            totalRiwayat += total;
            totalLengkap += lengkap;
            detail.kondisional.items[jenis] = items;
        }
    });

    detail.kondisional.total = totalRiwayat;
    detail.kondisional.lengkap = totalLengkap;

    let skorKondisional = 0;
    if (totalRiwayat > 0) {
        skorKondisional = (totalLengkap / totalRiwayat) * 10;
    } else {
        skorKondisional = 10; // BONUS +10 jika tidak punya kondisional
    }

    detail.skorUtama = skorUtama;
    detail.skorKondisional = skorKondisional;

    return detail;
}
</script>
@endpush
@endsection
