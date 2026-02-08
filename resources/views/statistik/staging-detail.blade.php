@extends('layouts.app')

@section('title', 'Detail Logs Staging')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Detail Logs Staging</h4>
                        <p class="text-muted mb-0">Logs untuk pegawai yang belum terdata</p>
                    </div>
                    <a href="{{ route('staging.index') }}" class="btn btn-outline-secondary">
                        <i class="ti-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Pegawai Info -->
                <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                    <i class="ti-user icon-lg me-3"></i>
                    <div class="flex-grow-1">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>NIP:</strong> {{ $pegawaiInfo->created_by_nip }}
                            </div>
                            <div class="col-md-4">
                                <strong>Nama:</strong> {{ $pegawaiInfo->created_by_nama }}
                            </div>
                            <div class="col-md-4">
                                <strong>Total Logs:</strong> <span class="badge badge-warning badge-pill">{{ number_format($totalLogs) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mb-3">
                    <a href="{{ route('pegawai.create', ['nip' => $pegawaiInfo->created_by_nip, 'nama' => $pegawaiInfo->created_by_nama]) }}"
                       class="btn btn-primary">
                        <i class="ti-plus"></i> Tambah Pegawai ke Database
                    </a>
                    <button type="button"
                            class="btn btn-success btn-process-logs"
                            data-nip="{{ $pegawaiInfo->created_by_nip }}"
                            data-nama="{{ $pegawaiInfo->created_by_nama }}">
                        <i class="ti-check"></i> Proses Logs ke Aktivitas
                    </button>
                </div>

                <!-- Logs Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-warning">
                            <tr>
                                <th width="50">No</th>
                                <th width="120">Timestamp</th>
                                <th width="150">Event Name</th>
                                <th>Details</th>
                                <th width="200">Transaction ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $index => $log)
                                <tr>
                                    <td>{{ $logs->firstItem() + $index }}</td>
                                    <td>
                                        <small class="text-muted">{{ $log->created_at_log ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline-info">{{ $log->event_name }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $log->details ?? '-' }}</strong>
                                    </td>
                                    <td>
                                        <code class="text-small">{{ substr($log->transaction_id, 0, 8) }}...</code>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($logs->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ $logs->firstItem() }} - {{ $logs->lastItem() }} dari {{ $logs->total() }} logs
                    </div>
                    <div>
                        {{ $logs->links('pagination::bootstrap-5') }}
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
    .text-small {
        font-size: 0.75rem;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.btn-process-logs').addEventListener('click', function() {
        const nip = this.dataset.nip;
        const nama = this.dataset.nama;

        if (!confirm(`Apakah Anda yakin ingin memproses logs untuk ${nama} (${nip})?\n\nPastikan pegawai sudah ditambahkan ke database terlebih dahulu.`)) {
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="ti-reload"></i> Processing...';

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
                window.location.href = '{{ route("staging.index") }}';
            } else {
                alert('Error: ' + data.message);
                this.disabled = false;
                this.innerHTML = '<i class="ti-check"></i> Proses Logs ke Aktivitas';
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
            this.disabled = false;
            this.innerHTML = '<i class="ti-check"></i> Proses Logs ke Aktivitas';
        });
    });
});
</script>
@endsection
