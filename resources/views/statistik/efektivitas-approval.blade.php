@extends('layouts.app')

@section('title', 'Efektivitas Approval Dokumen MyASN')

@section('content')

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="mdi mdi-check-circle me-2"></i>Efektivitas Approval Dokumen MyASN
                </h5>
                <p class="mb-0 small mt-1">
                    Hitung efektivitas: Total Approval Dokumen MyASN / Total Jam Kerja ASN (7.5 jam/hari, Senin-Jumat)
                </p>
            </div>
            <div class="card-body">
                <!-- Filter Tanggal -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label small"><i class="ti-calendar me-1"></i> Dari Tanggal</label>
                        <input type="date"
                               id="approvalDateFrom"
                               class="form-control"
                               max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small"><i class="ti-calendar me-1"></i> Sampai Tanggal</label>
                        <input type="date"
                               id="approvalDateTo"
                               class="form-control"
                               max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-success" onclick="loadEfektivitasApproval()">
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
                <div id="approvalLoading" class="text-center py-5" style="display:none;">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Menghitung efektivitas approval...</p>
                </div>

                <!-- Results -->
                <div id="approvalResults" style="display:none;">
                    <!-- Ringkasan -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block">Total Hari Kerja</small>
                                    <h3 class="mb-0 text-primary" id="approvalWorkingDays">0</h3>
                                    <small class="text-muted">hari</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block">Total Jam Kerja</small>
                                    <h3 class="mb-0 text-info" id="approvalWorkingHours">0</h3>
                                    <small class="text-muted">jam</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <small class="text-muted d-block">Total Approval</small>
                                    <h3 class="mb-0 text-success" id="approvalTotalApproval">0</h3>
                                    <small class="text-muted">dokumen</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success">
                                <div class="card-body text-center">
                                    <small class="text-white d-block"><strong>Efektivitas Total</strong></small>
                                    <h3 class="mb-0 text-white" id="approvalTotal">0</h3>
                                    <small class="text-white"><strong>dok/jam</strong></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Buttons -->
                    <div id="approvalExportButtons" class="mb-3" style="display:none;">
                        <button type="button" class="btn btn-sm btn-success me-2" onclick="exportApproval('excel')">
                            <i class="ti-file-excel me-1"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="exportApproval('pdf')">
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
                                            <th width="15%" class="text-center">Total Approval</th>
                                            <th width="15%" class="text-center">Efektivitas</th>
                                        </tr>
                                    </thead>
                                    <tbody id="approvalTableBody">
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
function loadEfektivitasApproval() {
    const dateFrom = document.getElementById('approvalDateFrom').value;
    const dateTo = document.getElementById('approvalDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal mulai dan akhir terlebih dahulu');
        return;
    }

    const url = `{{ route('efektivitas-approval.data') }}?date_from=${dateFrom}&date_to=${dateTo}`;

    document.getElementById('approvalLoading').style.display = 'block';
    document.getElementById('approvalResults').style.display = 'none';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Update ringkasan dengan format angka
            document.getElementById('approvalWorkingDays').textContent = data.total_working_days.toLocaleString('id-ID');
            document.getElementById('approvalWorkingHours').textContent = data.total_working_hours.toLocaleString('id-ID');
            document.getElementById('approvalTotalApproval').textContent = data.total_approval.toLocaleString('id-ID');
            document.getElementById('approvalTotal').textContent = data.efektivitas_total;

            // Update tabel per pegawai
            const tableBody = document.getElementById('approvalTableBody');
            tableBody.innerHTML = '';

            if (data.efektivitas_per_pegawai.length > 0) {
                data.efektivitas_per_pegawai.forEach((item, index) => {
                    const row = `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td>${item.nip}</td>
                            <td>${item.nama}</td>
                            <td class="text-center">${item.total_approval.toLocaleString('id-ID')}</td>
                            <td class="text-center"><span class="badge badge-success">${item.efektivitas} dok/jam</span></td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            Tidak ada data approval dokumen MyASN pada periode ini
                        </td>
                    </tr>
                `;
            }

            document.getElementById('approvalLoading').style.display = 'none';
            document.getElementById('approvalResults').style.display = 'block';

            // Show export buttons
            document.getElementById('approvalExportButtons').style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading efektivitas approval:', error);
            document.getElementById('approvalLoading').style.display = 'none';
            alert('Gagal memuat data efektivitas approval');
        });
}

function exportApproval(type) {
    const dateFrom = document.getElementById('approvalDateFrom').value;
    const dateTo = document.getElementById('approvalDateTo').value;

    if (!dateFrom || !dateTo) {
        alert('Silakan pilih tanggal mulai dan akhir terlebih dahulu');
        return;
    }

    let url;
    if (type === 'excel') {
        url = `{{ route('efektivitas-approval.export-excel') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    } else {
        url = `{{ route('efektivitas-approval.export-pdf') }}?date_from=${dateFrom}&date_to=${dateTo}`;
    }

    window.open(url, '_blank');
}
</script>

@endsection
