@extends('layouts.app')

@section('title', 'Efektivitas Kerja ASN')

@section('content')

<div class="row mb-3">
    <div class="col-12">
        <div class="page-header mb-3">
            <h3 class="page-title">
                <span class="page-title-icon bg-gradient-primary text-white me-2">
                    <i class="mdi mdi-chart-line"></i>
                </span> Efektivitas Kerja ASN
            </h3>
            <nav aria-label="breadcrumb">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('aktivitas-pegawai.index') }}">Aktivitas Pegawai</a></li>
                    <li class="breadcrumb-item active">Efektivitas Kerja</li>
                </ul>
            </nav>
        </div>

        <!-- Filter Tanggal Global -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label small"><i class="mdi mdi-calendar"></i> Dari Tanggal</label>
                        <input type="date" id="globalDateFrom" class="form-control" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small"><i class="mdi mdi-calendar"></i> Sampai Tanggal</label>
                        <input type="date" id="globalDateTo" class="form-control" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-primary" onclick="loadAllEfektivitas()">
                            <i class="mdi mdi-calculator"></i> Hitung Semua Efektivitas
                        </button>
                        <a href="{{ route('aktivitas-pegawai.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 1: Mapping Non-Inject -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-file-document me-2"></i>Efektivitas Mapping Dokumen Non-Inject
                </h5>
                <p class="mb-0 small mt-1">
                    Total Mapping Dokumen / Total Jam Kerja ASN (7.5 jam/hari, Senin-Jumat)
                </p>
            </div>
            <div class="card-body">
                <!-- Loading -->
                <div id="mappingLoading" class="text-center py-3" style="display:none;">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2 text-muted small">Menghitung efektivitas mapping...</p>
                </div>

                <!-- Results -->
                <div id="mappingResults" style="display:none;">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Hari Kerja</small>
                                    <h4 class="mb-0 text-primary" id="mappingWorkingDays">0</h4>
                                    <small class="text-muted">hari</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Jam Kerja</small>
                                    <h4 class="mb-0 text-info" id="mappingWorkingHours">0</h4>
                                    <small class="text-muted">jam</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Total Mapping</small>
                                    <h4 class="mb-0 text-success" id="mappingTotal">0</h4>
                                    <small class="text-muted">dokumen</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-gradient-info text-white">
                                <div class="card-body text-center p-2">
                                    <small class="d-block"><strong>Efektivitas</strong></small>
                                    <h4 class="mb-0" id="mappingEfektivitas">0</h4>
                                    <small><strong>dok/jam</strong></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metrik Tambahan -->
                    <div class="alert alert-info mb-3">
                        <h6 class="alert-heading"><i class="mdi mdi-information"></i> Metrik Detail:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Rata-rata per Pegawai:</strong> <span id="mappingAvgPerson">0</span> dokumen
                            </div>
                            <div class="col-md-4">
                                <strong>Waktu per Dokumen:</strong> <span id="mappingMinutesPerDoc">0</span> menit
                            </div>
                            <div class="col-md-4">
                                <strong>Dokumen per Menit:</strong> <span id="mappingDocsPerMinute">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-sm btn-success" onclick="exportMapping('excel')">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="exportMapping('pdf')">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-info">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">NIP</th>
                                    <th width="40%">Nama</th>
                                    <th width="20%" class="text-center">Total</th>
                                    <th width="15%" class="text-center">Efektivitas</th>
                                </tr>
                            </thead>
                            <tbody id="mappingTableBody">
                                <tr><td colspan="5" class="text-center text-muted">Klik "Hitung Semua Efektivitas"</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Approval Dokumen MyASN -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-check-circle me-2"></i>Efektivitas Approval Dokumen MyASN
                </h5>
                <p class="mb-0 small mt-1">
                    Total Approval Dokumen / Total Jam Kerja ASN (7.5 jam/hari, Senin-Jumat)
                </p>
            </div>
            <div class="card-body">
                <!-- Loading -->
                <div id="approvalLoading" class="text-center py-3" style="display:none;">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2 text-muted small">Menghitung efektivitas approval...</p>
                </div>

                <!-- Results -->
                <div id="approvalResults" style="display:none;">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Hari Kerja</small>
                                    <h4 class="mb-0 text-primary" id="approvalWorkingDays">0</h4>
                                    <small class="text-muted">hari</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Jam Kerja</small>
                                    <h4 class="mb-0 text-info" id="approvalWorkingHours">0</h4>
                                    <small class="text-muted">jam</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Total Approval</small>
                                    <h4 class="mb-0 text-success" id="approvalTotal">0</h4>
                                    <small class="text-muted">approval</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-gradient-success text-white">
                                <div class="card-body text-center p-2">
                                    <small class="d-block"><strong>Efektivitas</strong></small>
                                    <h4 class="mb-0" id="approvalEfektivitas">0</h4>
                                    <small><strong>apr/jam</strong></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metrik Tambahan Approval -->
                    <div class="alert alert-success mb-3">
                        <h6 class="alert-heading"><i class="mdi mdi-information"></i> Metrik Detail:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Rata-rata per Pegawai:</strong> <span id="approvalAvgPerson">0</span> approval
                            </div>
                            <div class="col-md-4">
                                <strong>Waktu per Approval:</strong> <span id="approvalMinutesPerApproval">0</span> menit
                            </div>
                            <div class="col-md-4">
                                <strong>Approval per Menit:</strong> <span id="approvalPerMinute">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-sm btn-success" onclick="exportApproval('excel')">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="exportApproval('pdf')">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-success">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">NIP</th>
                                    <th width="40%">Nama</th>
                                    <th width="20%" class="text-center">Total</th>
                                    <th width="15%" class="text-center">Efektivitas</th>
                                </tr>
                            </thead>
                            <tbody id="approvalTableBody">
                                <tr><td colspan="5" class="text-center text-muted">Klik "Hitung Semua Efektivitas"</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Laporan Kekurangan Berkas -->
        <div class="card mb-3">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-alert-circle me-2"></i>Efektivitas Laporan Kekurangan Berkas
                </h5>
                <p class="mb-0 small mt-1">
                    Total Laporan Kekurangan / Total Jam Kerja ASN (7.5 jam/hari, Senin-Jumat)
                </p>
            </div>
            <div class="card-body">
                <!-- Loading -->
                <div id="laporanLoading" class="text-center py-3" style="display:none;">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="mt-2 text-muted small">Menghitung efektivitas laporan...</p>
                </div>

                <!-- Results -->
                <div id="laporanResults" style="display:none;">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Hari Kerja</small>
                                    <h4 class="mb-0 text-primary" id="laporanWorkingDays">0</h4>
                                    <small class="text-muted">hari</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Jam Kerja</small>
                                    <h4 class="mb-0 text-info" id="laporanWorkingHours">0</h4>
                                    <small class="text-muted">jam</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-2">
                                    <small class="text-muted d-block">Total Laporan</small>
                                    <h4 class="mb-0 text-warning" id="laporanTotal">0</h4>
                                    <small class="text-muted">laporan</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-gradient-warning text-white">
                                <div class="card-body text-center p-2">
                                    <small class="d-block"><strong>Efektivitas</strong></small>
                                    <h4 class="mb-0" id="laporanEfektivitas">0</h4>
                                    <small><strong>lap/jam</strong></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Metrik Tambahan Laporan -->
                    <div class="alert alert-warning mb-3">
                        <h6 class="alert-heading"><i class="mdi mdi-information"></i> Metrik Detail:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Rata-rata per Pegawai:</strong> <span id="laporanAvgPerson">0</span> laporan
                            </div>
                            <div class="col-md-4">
                                <strong>Waktu per Laporan:</strong> <span id="laporanMinutesPerLaporan">0</span> menit
                            </div>
                            <div class="col-md-4">
                                <strong>Laporan per Menit:</strong> <span id="laporanPerMinute">0</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-sm btn-success" onclick="exportLaporan('excel')">
                            <i class="mdi mdi-file-excel"></i> Export Excel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="exportLaporan('pdf')">
                            <i class="mdi mdi-file-pdf"></i> Export PDF
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-warning">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">NIP</th>
                                    <th width="40%">Nama</th>
                                    <th width="20%" class="text-center">Total</th>
                                    <th width="15%" class="text-center">Efektivitas</th>
                                </tr>
                            </thead>
                            <tbody id="laporanTableBody">
                                <tr><td colspan="5" class="text-center text-muted">Klik "Hitung Semua Efektivitas"</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load semua efektivitas sekaligus
function loadAllEfektivitas() {
    const dateFrom = document.getElementById('globalDateFrom').value;
    const dateTo = document.getElementById('globalDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal mulai dan akhir terlebih dahulu');
        return;
    }

    // Load ketiga efektivitas secara paralel
    loadMapping(dateFrom, dateTo);
    loadApproval(dateFrom, dateTo);
    loadLaporan(dateFrom, dateTo);
}

// Mapping
function loadMapping(dateFrom, dateTo) {
    document.getElementById('mappingLoading').style.display = 'block';
    document.getElementById('mappingResults').style.display = 'none';

    fetch(`{{ route('efektivitas-kerja.data') }}?date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('mappingWorkingDays').textContent = data.total_working_days.toLocaleString('id-ID');
            document.getElementById('mappingWorkingHours').textContent = data.total_working_hours.toLocaleString('id-ID');
            document.getElementById('mappingTotal').textContent = data.total_mapping.toLocaleString('id-ID');
            document.getElementById('mappingEfektivitas').textContent = data.efektivitas_total;

            // Update metrik tambahan
            document.getElementById('mappingAvgPerson').textContent = data.avg_per_person.toLocaleString('id-ID');
            document.getElementById('mappingMinutesPerDoc').textContent = data.minutes_per_doc.toLocaleString('id-ID');
            document.getElementById('mappingDocsPerMinute').textContent = data.docs_per_minute;

            const tbody = document.getElementById('mappingTableBody');
            tbody.innerHTML = '';
            if (data.efektivitas_per_pegawai.length > 0) {
                data.efektivitas_per_pegawai.forEach((item, i) => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="text-center">${i + 1}</td>
                            <td>${item.nip}</td>
                            <td>${item.nama}</td>
                            <td class="text-center">${item.total_mapping.toLocaleString('id-ID')}</td>
                            <td class="text-center"><span class="badge badge-info">${item.efektivitas}</span></td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>';
            }

            document.getElementById('mappingLoading').style.display = 'none';
            document.getElementById('mappingResults').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('mappingLoading').style.display = 'none';
            alert('Gagal memuat data mapping');
        });
}

function exportMapping(type) {
    const dateFrom = document.getElementById('globalDateFrom').value;
    const dateTo = document.getElementById('globalDateTo').value;
    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal terlebih dahulu');
        return;
    }
    const url = type === 'excel'
        ? `{{ route('efektivitas-kerja.export-excel') }}?date_from=${dateFrom}&date_to=${dateTo}`
        : `{{ route('efektivitas-kerja.export-pdf') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    window.open(url, '_blank');
}

// Approval
function loadApproval(dateFrom, dateTo) {
    document.getElementById('approvalLoading').style.display = 'block';
    document.getElementById('approvalResults').style.display = 'none';

    fetch(`{{ route('efektivitas-approval.data') }}?date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('approvalWorkingDays').textContent = data.total_working_days.toLocaleString('id-ID');
            document.getElementById('approvalWorkingHours').textContent = data.total_working_hours.toLocaleString('id-ID');
            document.getElementById('approvalTotal').textContent = data.total_approval.toLocaleString('id-ID');
            document.getElementById('approvalEfektivitas').textContent = data.efektivitas_total;

            // Update metrik tambahan
            document.getElementById('approvalAvgPerson').textContent = data.avg_per_person.toLocaleString('id-ID');
            document.getElementById('approvalMinutesPerApproval').textContent = data.minutes_per_approval.toLocaleString('id-ID');
            document.getElementById('approvalPerMinute').textContent = data.approvals_per_minute;

            const tbody = document.getElementById('approvalTableBody');
            tbody.innerHTML = '';
            if (data.efektivitas_per_pegawai.length > 0) {
                data.efektivitas_per_pegawai.forEach((item, i) => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="text-center">${i + 1}</td>
                            <td>${item.nip}</td>
                            <td>${item.nama}</td>
                            <td class="text-center">${item.total_approval.toLocaleString('id-ID')}</td>
                            <td class="text-center"><span class="badge badge-success">${item.efektivitas}</span></td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>';
            }

            document.getElementById('approvalLoading').style.display = 'none';
            document.getElementById('approvalResults').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('approvalLoading').style.display = 'none';
            alert('Gagal memuat data approval');
        });
}

function exportApproval(type) {
    const dateFrom = document.getElementById('globalDateFrom').value;
    const dateTo = document.getElementById('globalDateTo').value;
    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal terlebih dahulu');
        return;
    }
    const url = type === 'excel'
        ? `{{ route('efektivitas-approval.export-excel') }}?date_from=${dateFrom}&date_to=${dateTo}`
        : `{{ route('efektivitas-approval.export-pdf') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    window.open(url, '_blank');
}

// Laporan Kekurangan
function loadLaporan(dateFrom, dateTo) {
    document.getElementById('laporanLoading').style.display = 'block';
    document.getElementById('laporanResults').style.display = 'none';

    fetch(`{{ route('efektivitas-laporan.data') }}?date_from=${dateFrom}&date_to=${dateTo}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('laporanWorkingDays').textContent = data.total_working_days.toLocaleString('id-ID');
            document.getElementById('laporanWorkingHours').textContent = data.total_working_hours.toLocaleString('id-ID');
            document.getElementById('laporanTotal').textContent = data.total_laporan.toLocaleString('id-ID');
            document.getElementById('laporanEfektivitas').textContent = data.efektivitas_total;

            // Update metrik tambahan
            document.getElementById('laporanAvgPerson').textContent = data.avg_per_person.toLocaleString('id-ID');
            document.getElementById('laporanMinutesPerLaporan').textContent = data.minutes_per_laporan.toLocaleString('id-ID');
            document.getElementById('laporanPerMinute').textContent = data.laporan_per_minute;

            const tbody = document.getElementById('laporanTableBody');
            tbody.innerHTML = '';
            if (data.efektivitas_per_pegawai.length > 0) {
                data.efektivitas_per_pegawai.forEach((item, i) => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="text-center">${i + 1}</td>
                            <td>${item.nip}</td>
                            <td>${item.nama}</td>
                            <td class="text-center">${item.total_laporan.toLocaleString('id-ID')}</td>
                            <td class="text-center"><span class="badge badge-warning">${item.efektivitas}</span></td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>';
            }

            document.getElementById('laporanLoading').style.display = 'none';
            document.getElementById('laporanResults').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('laporanLoading').style.display = 'none';
            alert('Gagal memuat data laporan kekurangan');
        });
}

function exportLaporan(type) {
    const dateFrom = document.getElementById('globalDateFrom').value;
    const dateTo = document.getElementById('globalDateTo').value;
    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal terlebih dahulu');
        return;
    }
    const url = type === 'excel'
        ? `{{ route('efektivitas-laporan.export-excel') }}?date_from=${dateFrom}&date_to=${dateTo}`
        : `{{ route('efektivitas-laporan.export-pdf') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    window.open(url, '_blank');
}

// Set default dates (bulan ini)
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);

    document.getElementById('globalDateFrom').value = firstDay.toISOString().split('T')[0];
    document.getElementById('globalDateTo').value = today.toISOString().split('T')[0];
});
</script>

@endsection
