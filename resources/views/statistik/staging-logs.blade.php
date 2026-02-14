@extends('layouts.app')

@section('title', 'Pegawai Belum Terdata')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Pegawai Belum Terdata</h4>
                        <p class="text-muted mb-0">Daftar pegawai yang memiliki log aktivitas tapi belum terdaftar di database</p>
                    </div>
                    <a href="{{ route('aktivitas-pegawai.index') }}" class="btn btn-outline-secondary">
                        <i class="ti-arrow-left"></i> Kembali
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="alert alert-warning" role="alert">
                    <i class="ti-info-alt me-2"></i>
                    <strong>Petunjuk:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Klik tombol <strong>"Tambah Pegawai"</strong> untuk menambahkan pegawai ke database</li>
                        <li>Setelah pegawai ditambahkan, klik <strong>"Proses Logs"</strong> untuk memindahkan log ke aktivitas utama</li>
                        <li>Logs akan otomatis masuk ke statistik setelah diproses</li>
                    </ol>
                </div>

                <!-- Search Form -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form action="{{ route('staging.index') }}" method="GET" id="searchForm">
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       name="search"
                                       placeholder="Cari NIP atau Nama..."
                                       value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti-search"></i> Cari
                                </button>
                                @if(request('search'))
                                    <a href="{{ route('staging.index') }}" class="btn btn-outline-secondary">
                                        <i class="ti-close"></i> Reset
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="text-muted small mt-2">
                            Total: <strong>{{ $stagingNips->total() }}</strong> pegawai belum terdata
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-warning">
                            <tr>
                                <th width="50">No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th class="text-center">Total Logs</th>
                                <th class="text-center">First Activity</th>
                                <th class="text-center">Last Activity</th>
                                <th class="text-center" width="250">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stagingNips as $index => $staging)
                                <tr>
                                    <td>{{ $stagingNips->firstItem() + $index }}</td>
                                    <td><code>{{ $staging->created_by_nip }}</code></td>
                                    <td><strong>{{ $staging->created_by_nama }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-warning badge-pill">{{ number_format($staging->total_logs) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $staging->first_activity }}</small>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $staging->last_activity }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('staging.show', $staging->created_by_nip) }}"
                                           class="btn btn-sm btn-outline-info me-1"
                                           title="Lihat Detail Logs">
                                            <i class="ti-eye"></i> Detail
                                        </a>
                                        <a href="{{ route('pegawai.create', ['nip' => $staging->created_by_nip, 'nama' => $staging->created_by_nama]) }}"
                                           class="btn btn-sm btn-outline-primary me-1"
                                           title="Tambah ke Pegawai">
                                            <i class="ti-plus"></i> Tambah Pegawai
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-success btn-process-logs"
                                                data-nip="{{ $staging->created_by_nip }}"
                                                data-nama="{{ $staging->created_by_nama }}"
                                                title="Proses Logs ke Aktivitas">
                                            <i class="ti-check"></i> Proses Logs
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="ti-check-box icon-lg"></i>
                                        <p class="mt-2">Semua pegawai sudah terdata! Tidak ada logs di staging.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($stagingNips->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ $stagingNips->firstItem() }} - {{ $stagingNips->lastItem() }} dari {{ $stagingNips->total() }} NIP
                    </div>
                    <div>
                        {{ $stagingNips->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .pagination .page-link {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle proses logs button
    document.querySelectorAll('.btn-process-logs').forEach(button => {
        button.addEventListener('click', function() {
            const nip = this.dataset.nip;
            const nama = this.dataset.nama;

            if (!confirm(`Apakah Anda yakin ingin memproses logs untuk ${nama} (${nip})?\n\nPastikan pegawai sudah ditambahkan ke database terlebih dahulu.`)) {
                return;
            }

            // Disable button dan show loading
            this.disabled = true;
            this.innerHTML = '<i class="ti-reload"></i> Processing...';

            // Send AJAX request
            fetch(`/statistik/staging/${nip}/process`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="ti-check"></i> Proses Logs';
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan: ' + error.message);
                this.disabled = false;
                this.innerHTML = '<i class="ti-check"></i> Proses Logs';
            });
        });
    });
});
</script>
@endsection
