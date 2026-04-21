@extends('layouts.app')

@section('title', 'Detail Arsip Pensiun')

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Info Upload -->
        <div class="card grid-margin">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">
                        <i class="mdi mdi-archive"></i> Detail Upload Arsip Pensiun
                    </h4>
                    <a href="{{ route('arsip-pensiun.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center p-3">
                                <small class="text-muted d-block">Nama Pengupload</small>
                                <strong>{{ $upload->nama_pengupload }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body text-center p-3">
                                <small class="text-muted d-block">Nama File</small>
                                <strong class="small">{{ $upload->zip_filename }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-success">
                            <div class="card-body text-center p-3">
                                <small class="text-muted d-block">Jumlah Dokumen</small>
                                <strong id="jumlahDokumen">{{ number_format($upload->jumlah_dokumen) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-warning">
                            <div class="card-body text-center p-3">
                                <small class="text-muted d-block">Tanggal Unggah</small>
                                <strong>{{ \Carbon\Carbon::parse($upload->tanggal_unggah)->format('d/m/Y') }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card" id="statusCard">
                            <div class="card-body text-center p-3">
                                <small class="text-muted d-block">Status</small>
                                <span id="statusBadge">
                                    @if($upload->status === 'completed')
                                        <span class="badge badge-success"><i class="mdi mdi-check-circle"></i> Selesai</span>
                                    @elseif($upload->status === 'extracting')
                                        <span class="badge badge-warning"><i class="mdi mdi-progress-clock"></i> Proses</span>
                                    @elseif($upload->status === 'pending')
                                        <span class="badge badge-secondary"><i class="mdi mdi-clock-outline"></i> Menunggu</span>
                                    @else
                                        <span class="badge badge-danger"><i class="mdi mdi-alert-circle"></i> Gagal</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Processing Message -->
                <div id="processingMessage" class="text-center mt-3"
                     style="{{ in_array($upload->status, ['pending', 'extracting']) ? '' : 'display:none;' }}">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2" id="statusMessage">{{ $upload->message }}</p>
                </div>
            </div>
        </div>

        <!-- Daftar File -->
        <div class="card" id="fileListCard" style="{{ $upload->status === 'completed' ? '' : 'display:none;' }}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">
                        <i class="mdi mdi-file-multiple"></i> Daftar Dokumen ({{ number_format($upload->jumlah_dokumen) }})
                    </h4>
                    @if($upload->jumlah_dokumen > 0)
                    <a href="{{ route('arsip-pensiun.download-all', $upload->id) }}" class="btn btn-success btn-sm">
                        <i class="mdi mdi-download me-1"></i> Download Semua (ZIP)
                    </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Nama File</th>
                                <th class="text-center" width="100">Tipe</th>
                                <th class="text-center" width="100">Ukuran</th>
                                <th class="text-center" width="80">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($files as $index => $file)
                            <tr>
                                <td>{{ $files->firstItem() + $index }}</td>
                                <td>
                                    <i class="mdi {{ str_contains($file->mime_type, 'pdf') ? 'mdi-file-pdf text-danger' : 'mdi-file-image text-info' }} me-1"></i>
                                    {{ $file->original_filename }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ str_contains($file->mime_type, 'pdf') ? 'badge-danger' : 'badge-info' }}">
                                        {{ strtoupper(pathinfo($file->original_filename, PATHINFO_EXTENSION)) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $size = $file->file_size;
                                        if ($size >= 1048576) $sizeStr = number_format($size / 1048576, 1) . ' MB';
                                        elseif ($size >= 1024) $sizeStr = number_format($size / 1024, 1) . ' KB';
                                        else $sizeStr = $size . ' B';
                                    @endphp
                                    <small>{{ $sizeStr }}</small>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('arsip-pensiun.download-file', [$upload->id, $file->id]) }}"
                                       class="btn btn-outline-primary btn-sm" title="Download">
                                        <i class="mdi mdi-download"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Tidak ada file</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($files->hasPages())
                <div class="mt-3">
                    {{ $files->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if(in_array($upload->status, ['pending', 'extracting']))
// Poll status every 3 seconds
const statusInterval = setInterval(function() {
    fetch('{{ route("arsip-pensiun.check-status", $upload->id) }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('statusMessage').textContent = data.message;
            document.getElementById('jumlahDokumen').textContent = Number(data.jumlah_dokumen).toLocaleString('id-ID');

            if (data.status === 'completed') {
                clearInterval(statusInterval);
                document.getElementById('statusBadge').innerHTML = '<span class="badge badge-success"><i class="mdi mdi-check-circle"></i> Selesai</span>';
                document.getElementById('processingMessage').style.display = 'none';
                // Reload to show file list
                window.location.reload();
            } else if (data.status === 'failed') {
                clearInterval(statusInterval);
                document.getElementById('statusBadge').innerHTML = '<span class="badge badge-danger"><i class="mdi mdi-alert-circle"></i> Gagal</span>';
                document.getElementById('processingMessage').innerHTML = '<div class="alert alert-danger"><i class="mdi mdi-alert-circle me-1"></i>' + data.message + '</div>';
            }
        })
        .catch(err => console.error('Error checking status:', err));
}, 3000);
@endif
</script>
@endpush
