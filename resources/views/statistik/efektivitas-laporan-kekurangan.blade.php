@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-warning text-white me-2">
                <i class="mdi mdi-alert-circle"></i>
            </span> Efektivitas Laporan Kekurangan Berkas
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('aktivitas-pegawai.index') }}">Aktivitas Pegawai</a></li>
                <li class="breadcrumb-item active" aria-current="page">Efektivitas Laporan Kekurangan</li>
            </ul>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="mdi mdi-alert-circle me-2"></i>Efektivitas Laporan Kekurangan Berkas
                    </h5>
                    <p class="mb-0 small mt-1">
                        Hitung efektivitas: Total Laporan Kekurangan Berkas / Total Jam Kerja ASN (7.5 jam/hari, Senin-Jumat)
                    </p>
                </div>
                <div class="card-body">
                    <!-- Filter Form -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="laporanDateFrom">Tanggal Mulai:</label>
                            <input type="date" class="form-control" id="laporanDateFrom">
                        </div>
                        <div class="col-md-4">
                            <label for="laporanDateTo">Tanggal Akhir:</label>
                            <input type="date" class="form-control" id="laporanDateTo">
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <div>
                                <button class="btn btn-warning btn-fw" onclick="loadEfektivitasLaporan()">
                                    <i class="mdi mdi-calculator"></i> Hitung Efektivitas
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="laporanLoading" style="display: none;" class="text-center my-4">
                        <div class="spinner-border text-warning" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Menghitung efektivitas laporan kekurangan...</p>
                    </div>

                    <!-- Results -->
                    <div id="laporanResults" style="display: none;">
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted">Total Hari Kerja</h6>
                                        <h3 class="text-warning" id="laporanWorkingDays">0</h3>
                                        <p class="small mb-0">hari (Senin-Jumat)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted">Total Jam Kerja</h6>
                                        <h3 class="text-warning" id="laporanWorkingHours">0</h3>
                                        <p class="small mb-0">jam (7.5 jam/hari)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted">Total Laporan Kekurangan</h6>
                                        <h3 class="text-warning" id="laporanTotalLaporan">0</h3>
                                        <p class="small mb-0">laporan</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-gradient-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Efektivitas Total</h6>
                                        <h3 id="laporanEfektivitasTotal">0.00</h3>
                                        <p class="small mb-0">laporan/jam</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Buttons -->
                        <div class="mb-3">
                            <button class="btn btn-success" onclick="exportLaporan('excel')">
                                <i class="mdi mdi-file-excel"></i> Export Excel
                            </button>
                            <button class="btn btn-danger" onclick="exportLaporan('pdf')">
                                <i class="mdi mdi-file-pdf"></i> Export PDF
                            </button>
                        </div>

                        <!-- Pegawai Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-warning">
                                    <tr>
                                        <th width="5%" class="text-center">No</th>
                                        <th width="20%">NIP</th>
                                        <th width="40%">Nama</th>
                                        <th width="20%" class="text-center">Total Laporan</th>
                                        <th width="15%" class="text-center">Efektivitas</th>
                                    </tr>
                                </thead>
                                <tbody id="laporanTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center">Silakan pilih tanggal dan klik "Hitung Efektivitas"</td>
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

<script>
function loadEfektivitasLaporan() {
    const dateFrom = document.getElementById('laporanDateFrom').value;
    const dateTo = document.getElementById('laporanDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal mulai dan akhir terlebih dahulu');
        return;
    }

    const url = `{{ route('efektivitas-laporan.data') }}?date_from=${dateFrom}&date_to=${dateTo}`;

    document.getElementById('laporanLoading').style.display = 'block';
    document.getElementById('laporanResults').style.display = 'none';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Update summary cards with Indonesian number formatting
            document.getElementById('laporanWorkingDays').textContent = data.total_working_days.toLocaleString('id-ID');
            document.getElementById('laporanWorkingHours').textContent = data.total_working_hours.toLocaleString('id-ID');
            document.getElementById('laporanTotalLaporan').textContent = data.total_laporan.toLocaleString('id-ID');
            document.getElementById('laporanEfektivitasTotal').textContent = data.efektivitas_total;

            // Populate pegawai table
            const tableBody = document.getElementById('laporanTableBody');
            tableBody.innerHTML = '';

            if (data.efektivitas_per_pegawai.length > 0) {
                data.efektivitas_per_pegawai.forEach((item, index) => {
                    const row = `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.nip}</td>
                            <td>${item.nama}</td>
                            <td class="text-center">${item.total_laporan.toLocaleString('id-ID')}</td>
                            <td class="text-center"><span class="badge badge-warning">${item.efektivitas} lap/jam</span></td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data untuk periode yang dipilih</td></tr>';
            }

            // Hide loading, show results
            document.getElementById('laporanLoading').style.display = 'none';
            document.getElementById('laporanResults').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data');
            document.getElementById('laporanLoading').style.display = 'none';
        });
}

function exportLaporan(type) {
    const dateFrom = document.getElementById('laporanDateFrom').value;
    const dateTo = document.getElementById('laporanDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal mulai dan akhir terlebih dahulu');
        return;
    }

    let url;
    if (type === 'excel') {
        url = `{{ route('efektivitas-laporan.export-excel') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    } else {
        url = `{{ route('efektivitas-laporan.export-pdf') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    }

    window.open(url, '_blank');
}

// Set default dates (current month)
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    document.getElementById('laporanDateFrom').value = firstDay.toISOString().split('T')[0];
    document.getElementById('laporanDateTo').value = lastDay.toISOString().split('T')[0];
});
</script>
@endsection
