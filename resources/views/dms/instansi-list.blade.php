@extends('layouts.app')

@section('title', 'Daftar Instansi DMS')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-0">Instansi yang Sudah Dihitung</h4>
                        <p class="text-muted mb-0">Daftar instansi dengan skor kelengkapan DMS</p>
                    </div>
                    <a href="{{ route('dashboard.dms') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>

                <!-- Search Form -->
                <form method="GET" action="{{ route('dms.instansi.all') }}" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Cari nama instansi..." value="{{ $search }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="mdi mdi-magnify"></i> Cari
                        </button>
                        @if($search)
                            <a href="{{ route('dms.instansi.all') }}" class="btn btn-outline-secondary">
                                <i class="mdi mdi-close"></i> Reset
                            </a>
                        @endif
                    </div>
                </form>

                @if($instansiList->isEmpty())
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i>
                        @if($search)
                            Tidak ada instansi yang ditemukan dengan kata kunci "{{ $search }}".
                        @else
                            Belum ada instansi yang dihitung. Silakan upload data DMS terlebih dahulu.
                        @endif
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">Nama Instansi</th>
                                    <th width="10%" class="text-center">Total PNS</th>
                                    <th width="12%" class="text-center">Skor System</th>
                                    <th width="12%" class="text-center">Skor CSV</th>
                                    <th width="15%">Status Kelengkapan</th>
                                    <th width="16%">Terakhir Update</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($instansiList as $index => $instansi)
                                    @php
                                        $kelengkapanBadge = match($instansi->status_kelengkapan) {
                                            'Sangat Lengkap' => 'bg-success',
                                            'Lengkap' => 'bg-primary',
                                            'Cukup Lengkap' => 'bg-warning',
                                            'Kurang Lengkap' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <tr style="cursor: pointer;" onclick="window.location='{{ route('dms.instansi.detail-full', $instansi->instansi_id) }}'">
                                        <td>{{ $instansiList->firstItem() + $index }}</td>
                                        <td>
                                            <strong>{{ $instansi->instansi_nama }}</strong>
                                        </td>
                                        <td class="text-center">{{ number_format($instansi->total_pns) }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ number_format($instansi->skor_instansi_calculated_system, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ number_format($instansi->skor_instansi_calculated_csv, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $kelengkapanBadge }}">{{ $instansi->status_kelengkapan }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($instansi->calculated_at)->format('d M Y H:i') }}
                                                <br>
                                                <span class="text-primary">({{ \Carbon\Carbon::parse($instansi->calculated_at)->diffForHumans() }})</span>
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $instansiList->links() }}
                    </div>

                    <div class="mt-3">
                        <p class="text-muted small mb-0">
                            Menampilkan {{ $instansiList->firstItem() }} - {{ $instansiList->lastItem() }} dari {{ $instansiList->total() }} instansi
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
tr[style*="cursor: pointer"]:hover {
    background-color: #f8f9fa;
}
.pagination {
    --bs-pagination-padding-x: 0.5rem !important;
    --bs-pagination-padding-y: 0.25rem !important;
    --bs-pagination-font-size: 0.875rem !important;
    --bs-pagination-border-color: #dee2e6 !important;
    --bs-pagination-color: #6c757d !important;
    margin-bottom: 0 !important;
}
.pagination .page-link {
    border-color: #dee2e6 !important;
    color: #6c757d !important;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #fff !important;
}
.pagination .page-link:hover {
    background-color: #e9ecef !important;
    color: #0d6efd !important;
}
</style>
@endpush
@endsection
