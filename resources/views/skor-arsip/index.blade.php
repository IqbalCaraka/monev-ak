@extends('layouts.app')

@section('title', 'Perhitungan Skor Arsip')

@section('content')

<div class="row">
    <div class="col-lg-12">
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
    const fileInput = document.getElementById('csv_file');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.getElementById('uploadForm');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // File preview
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
});
</script>

@endsection
