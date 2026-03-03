@extends('layouts.app')

@section('title', 'Perhitungan Skor Arsip')

@section('content')

<div class="row">
    <div class="col-sm-12">
        <div class="home-tab">
            <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active ps-0" id="skor-arsip-tab" data-bs-toggle="tab" href="#skor-arsip" role="tab" aria-controls="skor-arsip" aria-selected="true">Perhitungan Skor Arsip</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="ubah-format-tab" data-bs-toggle="tab" href="#ubah-format" role="tab" aria-controls="ubah-format" aria-selected="false">Ubah Format</a>
                    </li>
                </ul>
            </div>
            <div class="tab-content tab-content-basic">
                <!-- Tab: Perhitungan Skor Arsip -->
                <div class="tab-pane fade show active" id="skor-arsip" role="tabpanel" aria-labelledby="skor-arsip">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-4">
                                <h4 class="card-title mb-1">Perhitungan Skor Arsip Pegawai</h4>
                                <p class="text-muted mb-0">Upload file CSV untuk menghitung dan menampilkan skor arsip pegawai</p>
                            </div>

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Terjadi kesalahan:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <!-- Info Panel -->
                            <div class="alert alert-info" role="alert">
                                <h5 class="alert-heading"><i class="mdi mdi-information"></i> Format CSV yang Dibutuhkan</h5>
                                <hr>
                                <p class="mb-2"><strong>Data yang akan ditampilkan:</strong></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="mb-2">
                                            <li><strong>Kolom B:</strong> NIP</li>
                                            <li><strong>Kolom C:</strong> Nama</li>
                                            <li><strong>Kolom D:</strong> Status CPNS/PNS (P atau C)</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="mb-2">
                                            <li><strong>Kolom W-AO:</strong> Data jumlah dan dokumen</li>
                                            <li><strong>Kolom BH-BL:</strong> Data skor arsip dan kategori</li>
                                        </ul>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <p class="mb-0">
                                    <i class="mdi mdi-alert-circle"></i>
                                    <small>File CSV harus menggunakan delimiter <strong>titik koma (;)</strong> dan maksimal 10MB. Data tidak akan disimpan ke database, hanya ditampilkan.</small>
                                </p>
                            </div>

                            <!-- Upload Form -->
                            <form action="{{ route('skor-arsip.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                                @csrf

                                <div class="mb-4">
                                    <label for="csv_file" class="form-label">
                                        Pilih File CSV <span class="text-danger">*</span>
                                    </label>
                                    <input type="file"
                                           class="form-control @error('csv_file') is-invalid @enderror"
                                           id="csv_file"
                                           name="csv_file"
                                           accept=".csv,.txt"
                                           required>
                                    @error('csv_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Format: CSV dengan delimiter titik koma (;), Max: 10MB</small>
                                </div>

                                <div id="filePreview" class="mb-4" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">Preview File:</h6>
                                            <div class="d-flex align-items-center">
                                                <i class="mdi mdi-file-document-outline icon-lg text-primary me-3"></i>
                                                <div>
                                                    <p class="mb-1"><strong id="fileName"></strong></p>
                                                    <small class="text-muted" id="fileSize"></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-calculator"></i> Proses & Tampilkan Data
                                    </button>
                                </div>
                            </form>

                            <!-- Example Preview -->
                            <div class="mt-5">
                                <h5 class="mb-3"><i class="mdi mdi-eye"></i> Preview Hasil (Contoh)</h5>
                                <div class="alert alert-secondary">
                                    <p class="mb-2">Setelah upload CSV, data akan ditampilkan dalam format tabel dengan informasi:</p>
                                    <ul class="mb-0">
                                        <li>Statistik ringkasan (Total Data, PNS, CPNS, Kelengkapan)</li>
                                        <li>Tabel detail per pegawai dengan NIP, Nama, Status, Total Jumlah Data, Total Dokumen, dan Skor Arsip 2026</li>
                                        <li>Fitur pencarian dan filter data</li>
                                        <li>Detail lengkap dapat dilihat dengan klik tombol "Detail"</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Ubah Format -->
                <div class="tab-pane fade" id="ubah-format" role="tabpanel" aria-labelledby="ubah-format-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-4">
                                <h4 class="card-title mb-1">Ubah Format Status Arsip</h4>
                                <p class="text-muted mb-0">Upload CSV export SAPA untuk generate Excel dengan format terkelompok</p>
                            </div>

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Berhasil!</strong> {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <!-- Info Panel -->
                            <div class="alert alert-info" role="alert">
                                <h5 class="alert-heading"><i class="mdi mdi-information"></i> Informasi</h5>
                                <hr>
                                <p class="mb-2"><strong>File yang akan dihasilkan (Excel):</strong></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Data Utama (5 sheet):</strong>
                                        <ul class="mb-2 mt-2">
                                            <li>D2NP - grouped by golongan</li>
                                            <li>DRH - grouped by golongan</li>
                                            <li>SPMT CPNS - grouped by golongan</li>
                                            <li>CPNS - grouped by golongan</li>
                                            <li>PNS - grouped by golongan</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Riwayat (10 sheet):</strong>
                                        <ul class="mb-2 mt-2">
                                            <li>Golongan - per ID golongan</li>
                                            <li>Pendidikan - per tingkat</li>
                                            <li>Jabatan - per tahun</li>
                                            <li>Diklat - per tahun</li>
                                            <li>Pindah Instansi - per tahun</li>
                                            <li>Penghargaan - per tahun</li>
                                            <li>SKP - per tahun</li>
                                            <li>Angka Kredit</li>
                                            <li>CLTN (Cuti LN)</li>
                                            <li>PMK (Masa Kerja)</li>
                                        </ul>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <p class="mb-0">
                                    <i class="mdi mdi-alert-circle"></i>
                                    <small>CSV harus memiliki kolom <strong>status_arsip</strong> (JSON format). Setiap sheet akan mengelompokkan PNS berdasarkan status: <span class="badge bg-success">LENGKAP (dokumen ada)</span> dan <span class="badge bg-danger">TIDAK LENGKAP (riwayat ada, dokumen tidak ada)</span></small>
                                </p>
                            </div>

                            <!-- Upload Form -->
                            <form action="{{ route('ubah-format.process') }}" method="POST" enctype="multipart/form-data" id="uploadFormUbahFormat">
                                @csrf

                                <div class="mb-4">
                                    <label for="csv_file_ubah" class="form-label">
                                        Pilih File CSV (Export SAPA) <span class="text-danger">*</span>
                                    </label>
                                    <input type="file"
                                           class="form-control"
                                           id="csv_file_ubah"
                                           name="csv_file"
                                           accept=".csv,.txt"
                                           required>
                                    <small class="text-muted">Format: CSV, Max: 10MB</small>
                                </div>

                                <div id="filePreviewUbah" class="mb-4" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">Preview File:</h6>
                                            <div class="d-flex align-items-center">
                                                <i class="mdi mdi-file-excel-outline icon-lg text-success me-3"></i>
                                                <div>
                                                    <p class="mb-1"><strong id="fileNameUbah"></strong></p>
                                                    <small class="text-muted" id="fileSizeUbah"></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="mdi mdi-file-excel"></i> Generate Excel
                                    </button>
                                </div>
                            </form>

                            <!-- Example Sheet Structure -->
                            <div class="mt-5">
                                <h5 class="mb-3"><i class="mdi mdi-eye"></i> Contoh Struktur Sheet</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="mb-2">Sheet: Golongan III/A (31)</h6>
                                        <div class="alert alert-success mb-2">
                                            <strong>LENGKAP (Dokumen Ada)</strong><br>
                                            <small>NIP | Nama</small><br>
                                            199212262022032004 | SITI RODIYAH
                                        </div>
                                        <div class="alert alert-danger mb-0">
                                            <strong>TIDAK LENGKAP (Riwayat Ada, Dokumen Tidak Ada)</strong><br>
                                            <small>NIP | Nama</small><br>
                                            199301272019011001 | MUHAMMAD SAMI DARYANTO
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div class="loading-content text-center">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-light mt-3 mb-0">Memproses file CSV...</p>
        <small class="text-light">Mohon tunggu</small>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab 1: Skor Arsip
    const fileInput = document.getElementById('csv_file');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.getElementById('uploadForm');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // File preview for skor arsip
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                fileSize.textContent = `Ukuran: ${sizeInMB} MB`;
                filePreview.style.display = 'block';

                // Validate file size
                if (file.size > 10 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB');
                    fileInput.value = '';
                    filePreview.style.display = 'none';
                }

                // Validate file extension
                const ext = file.name.split('.').pop().toLowerCase();
                if (ext !== 'csv' && ext !== 'txt') {
                    alert('Format file tidak didukung! Gunakan file CSV');
                    fileInput.value = '';
                    filePreview.style.display = 'none';
                }
            } else {
                filePreview.style.display = 'none';
            }
        });

        // Show loading on form submit
        uploadForm.addEventListener('submit', function(e) {
            loadingOverlay.style.display = 'flex';
        });
    }

    // Tab 2: Ubah Format
    const fileInputUbah = document.getElementById('csv_file_ubah');
    const filePreviewUbah = document.getElementById('filePreviewUbah');
    const fileNameUbah = document.getElementById('fileNameUbah');
    const fileSizeUbah = document.getElementById('fileSizeUbah');
    const uploadFormUbah = document.getElementById('uploadFormUbahFormat');

    // File preview for ubah format
    if (fileInputUbah) {
        fileInputUbah.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileNameUbah.textContent = file.name;
                const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                fileSizeUbah.textContent = `Ukuran: ${sizeInMB} MB`;
                filePreviewUbah.style.display = 'block';

                // Validate file size
                if (file.size > 10 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar! Maksimal 10MB');
                    fileInputUbah.value = '';
                    filePreviewUbah.style.display = 'none';
                }

                // Validate file extension
                const ext = file.name.split('.').pop().toLowerCase();
                if (ext !== 'csv' && ext !== 'txt') {
                    alert('Format file tidak didukung! Gunakan file CSV');
                    fileInputUbah.value = '';
                    filePreviewUbah.style.display = 'none';
                }
            } else {
                filePreviewUbah.style.display = 'none';
            }
        });

        // Show loading on form submit
        uploadFormUbah.addEventListener('submit', function(e) {
            loadingOverlay.style.display = 'flex';
        });
    }

    // Auto-download Excel if available in session
    @if(session('excel_download'))
        const downloadUrl = '/temp/{{ session('excel_download') }}';
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = '{{ session('excel_download') }}';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        // Delete the file after download
        setTimeout(function() {
            fetch(downloadUrl, { method: 'DELETE' });
        }, 2000);
    @endif
});
</script>

@endsection
