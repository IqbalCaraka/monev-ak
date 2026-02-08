@extends('layouts.app')

@section('title', 'Detail Logs - ' . $kategori)

@section('content')

<!-- Date Filter Form -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('aktivitas-pegawai.detail-kategori', [$pegawai->nip, $kategori]) }}">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-1 small"><i class="ti-calendar me-1"></i> Dari Tanggal</label>
                            <input type="date"
                                   name="date_from"
                                   class="form-control form-control-sm"
                                   value="{{ $dateFrom ?? '' }}"
                                   max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1 small"><i class="ti-calendar me-1"></i> Sampai Tanggal</label>
                            <input type="date"
                                   name="date_to"
                                   class="form-control form-control-sm"
                                   value="{{ $dateTo ?? '' }}"
                                   max="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="ti-filter"></i> Filter
                            </button>
                            @if($dateFrom || $dateTo)
                                <a href="{{ route('aktivitas-pegawai.detail-kategori', [$pegawai->nip, $kategori]) }}" class="btn btn-sm btn-secondary">
                                    <i class="ti-reload"></i> Reset
                                </a>
                            @endif
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="{{ route('aktivitas-pegawai.show', $pegawai->nip) }}?date_from={{ $dateFrom ?? '' }}&date_to={{ $dateTo ?? '' }}" class="btn btn-sm btn-outline-secondary">
                                <i class="ti-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>

                    @if($dateFrom || $dateTo)
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="ti-info-alt"></i>
                                Menampilkan data dari
                                <strong>{{ $dateFrom ? date('d/m/Y', strtotime($dateFrom)) : 'awal' }}</strong>
                                sampai
                                <strong>{{ $dateTo ? date('d/m/Y', strtotime($dateTo)) : 'akhir' }}</strong>
                            </small>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="mb-4">
                    <h4 class="card-title mb-1">Detail Logs: {{ $kategori }}</h4>
                    <p class="text-muted mb-0">Detail aktivitas untuk kategori <strong>{{ $kategori }}</strong></p>
                </div>

                <!-- Pegawai Info -->
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="ti-user icon-lg me-3"></i>
                    <div class="flex-grow-1">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>NIP:</strong> {{ $pegawai->nip }}
                            </div>
                            <div class="col-md-4">
                                <strong>Nama:</strong> {{ $pegawai->nama }}
                            </div>
                            <div class="col-md-4">
                                <strong>Total Logs:</strong> <span class="badge badge-primary badge-pill">{{ number_format($totalLogs) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th width="50">No</th>
                                <th width="120">Timestamp</th>
                                <th width="150">Event Name</th>
                                <th>Details</th>
                                <th width="200">Transaction ID</th>
                                <th width="100">Object PNS ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $index => $log)
                                <tr>
                                    <td>{{ $logs->firstItem() + $index }}</td>
                                    <td>
                                        <small class="text-muted">{{ $log->created_at_log ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-outline-primary">{{ $log->event_name }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $log->details ?? '-' }}</strong>
                                        @if(str_starts_with($kategori, 'Inject'))
                                            <span class="badge badge-warning ms-1">Inject</span>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-small">{{ substr($log->transaction_id, 0, 8) }}...</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ substr($log->object_pns_id, 0, 8) }}...</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Tidak ada data log untuk kategori ini
                                    </td>
                                </tr>
                            @endforelse
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

                <!-- Info Note -->
                @if($kategori === 'Inject - Unggah Dokumen')
                <div class="alert alert-warning mt-4" role="alert">
                    <i class="ti-info-alt me-2"></i>
                    <strong>Catatan Inject - Unggah Dokumen:</strong> Logs ini merupakan aktivitas "unggah_dokumen" yang kolom details-nya berbeda dari "unggah_dokumen".
                    Artinya, ini adalah data yang di-inject/import, bukan upload dokumen biasa.
                </div>
                @elseif($kategori === 'Inject - Mapping Dokumen')
                <div class="alert alert-warning mt-4" role="alert">
                    <i class="ti-info-alt me-2"></i>
                    <strong>Catatan Inject - Mapping Dokumen:</strong> Logs ini merupakan aktivitas "mapping_dokumen" yang kolom details-nya mengandung kata "inject".
                    <br>Contoh: "Mapping dokumen SK_PNS ke riwayat pns untuk PNS IRDIANSYAH (197507072009011003) via <strong>Inject</strong> dari IP 10.100.8.111"
                </div>
                @elseif($kategori === 'Mapping Dokumen')
                <div class="alert alert-success mt-4" role="alert">
                    <i class="ti-info-alt me-2"></i>
                    <strong>Catatan Mapping Dokumen:</strong> Logs ini merupakan aktivitas "mapping_dokumen" yang dilakukan secara manual (tidak ada kata "inject" di details).
                    <br>Contoh details: "rw-golongan", "rw-diklat", "rw-jabatan"
                </div>
                @elseif($kategori === 'Unggah Dokumen')
                <div class="alert alert-info mt-4" role="alert">
                    <i class="ti-info-alt me-2"></i>
                    <strong>Catatan Unggah Dokumen:</strong> Logs ini merupakan aktivitas "unggah_dokumen" normal (details = "unggah_dokumen").
                    Ini adalah upload dokumen biasa, bukan inject.
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
@endsection
