@extends('layouts.app')

@section('title', 'Efektivitas Kerja Mapping Non-Inject')

@section('content')

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-chart-line me-2"></i>Efektivitas Kerja Mapping Non-Inject
                </h5>
                <p class="mb-0 small mt-1">
                    Hitung efektivitas: Total Mapping Non-Inject / Total Jam Kerja ASN (7.5 jam/hari, Senin-Jumat)
                </p>
            </div>
            <div class="card-body">
                <!-- Filter Tanggal -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label small"><i class="ti-calendar me-1"></i> Dari Tanggal</label>
                        <input type="date"
                               id="efektivitasDateFrom"
                               class="form-control"
                               max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small"><i class="ti-calendar me-1"></i> Sampai Tanggal</label>
                        <input type="date"
                               id="efektivitasDateTo"
                               class="form-control"
                               max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" onclick="loadEfektivitasKerja()">
                            <i class="ti-reload me-1"></i> Hitung Efektivitas
                        </button>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <a href="{{ route('aktivitas-pegawai.index') }}" class="btn btn-secondary">
                            <i class="ti-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>

                <!-- Loading -->
                <div id="efektivitasLoading" class="text-center py-5" style="display:none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Menghitung efektivitas kerja...</p>
                </div>

                <!-- Results -->
                <div id="efektivitasResults" style="display:none;">
                    <!-- Ringkasan -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block">Total Hari Kerja</small>
                                    <h3 class="mb-0 text-primary" id="efektivitasWorkingDays">0</h3>
                                    <small class="text-muted">hari</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block">Total Jam Kerja</small>
                                    <h3 class="mb-0 text-info" id="efektivitasWorkingHours">0</h3>
                                    <small class="text-muted">jam</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block">Total Mapping Non-Inject</small>
                                    <h3 class="mb-0 text-success" id="efektivitasTotalMapping">0</h3>
                                    <small class="text-muted">dokumen</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning">
                                <div class="card-body text-center">
                                    <small class="text-dark d-block"><strong>Efektivitas Total</strong></small>
                                    <h3 class="mb-0 text-dark" id="efektivitasTotal">0</h3>
                                    <small class="text-dark"><strong>dok/jam</strong></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Buttons -->
                    <div id="efektivitasExportButtons" class="mb-3" style="display:none;">
                        <button type="button" class="btn btn-sm btn-success me-2" onclick="exportEfektivitas('excel')">
                            <i class="ti-file-excel me-1"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="exportEfektivitas('pdf')">
                            <i class="ti-file-pdf me-1"></i> Export PDF
                        </button>
                        <small class="text-muted ms-2">
                            <i class="ti-info-alt"></i> Export berisi: Ringkasan Total, Per Minggu (7 hari), dan Per Hari
                        </small>
                    </div>

                    <!-- Tabel Per Pegawai -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Efektivitas Per Pegawai</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="table-success">
                                        <tr>
                                            <th width="10%" class="text-center">No</th>
                                            <th width="25%">NIP</th>
                                            <th width="35%">Nama</th>
                                            <th width="15%" class="text-center">Total Mapping</th>
                                            <th width="15%" class="text-center">Efektivitas</th>
                                        </tr>
                                    </thead>
                                    <tbody id="efektivitasTableBody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">
                                                Pilih tanggal dan klik "Hitung Efektivitas"
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

<script>
function loadEfektivitasKerja() {
    const dateFrom = document.getElementById('efektivitasDateFrom').value;
    const dateTo = document.getElementById('efektivitasDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal mulai dan akhir terlebih dahulu');
        return;
    }

    const url = `{{ route('efektivitas-kerja.data') }}?date_from=${dateFrom}&date_to=${dateTo}`;

    document.getElementById('efektivitasLoading').style.display = 'block';
    document.getElementById('efektivitasResults').style.display = 'none';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Update ringkasan dengan format angka
            document.getElementById('efektivitasWorkingDays').textContent = data.total_working_days.toLocaleString('id-ID');
            document.getElementById('efektivitasWorkingHours').textContent = data.total_working_hours.toLocaleString('id-ID');
            document.getElementById('efektivitasTotalMapping').textContent = data.total_mapping.toLocaleString('id-ID');
            document.getElementById('efektivitasTotal').textContent = data.efektivitas_total;

            // Update tabel per pegawai
            const tableBody = document.getElementById('efektivitasTableBody');
            tableBody.innerHTML = '';

            if (data.efektivitas_per_pegawai.length > 0) {
                data.efektivitas_per_pegawai.forEach((item, index) => {
                    const row = `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.nip}</td>
                            <td>${item.nama}</td>
                            <td class="text-center">${item.total_mapping.toLocaleString('id-ID')}</td>
                            <td class="text-center"><span class="badge badge-success">${item.efektivitas} dok/jam</span></td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            Tidak ada data mapping non-inject pada periode ini
                        </td>
                    </tr>
                `;
            }

            document.getElementById('efektivitasLoading').style.display = 'none';
            document.getElementById('efektivitasResults').style.display = 'block';

            // Show export buttons
            document.getElementById('efektivitasExportButtons').style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading efektivitas:', error);
            document.getElementById('efektivitasLoading').style.display = 'none';
            alert('Gagal memuat data efektivitas kerja');
        });
}

function exportEfektivitas(type) {
    const dateFrom = document.getElementById('efektivitasDateFrom').value;
    const dateTo = document.getElementById('efektivitasDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal mulai dan akhir terlebih dahulu');
        return;
    }

    let url;
    if (type === 'excel') {
        url = `{{ route('efektivitas-kerja.export-excel') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    } else {
        url = `{{ route('efektivitas-kerja.export-pdf') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    }

    window.open(url, '_blank');
}
</script>

@endsection
