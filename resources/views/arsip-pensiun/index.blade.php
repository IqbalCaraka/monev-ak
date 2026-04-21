@extends('layouts.app')

@section('title', 'Digitalisasi Arsip Pensiun')

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

        <!-- Upload Form -->
        <div class="card grid-margin">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-cloud-upload"></i> Upload Arsip Pensiun
                </h4>
                <p class="text-muted small mb-3">Upload file ZIP yang berisi dokumen arsip pensiun (PDF/gambar). Sistem akan mengekstrak file secara otomatis di background.</p>

                <form action="{{ route('arsip-pensiun.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nama_pengupload" class="form-label">Nama Pengupload <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_pengupload') is-invalid @enderror"
                                       id="nama_pengupload" name="nama_pengupload"
                                       value="{{ old('nama_pengupload') }}"
                                       placeholder="Masukkan nama pengupload" required>
                                @error('nama_pengupload')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="zip_file" class="form-label">File ZIP <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @error('zip_file') is-invalid @enderror"
                                       id="zip_file" name="zip_file"
                                       accept=".zip,.rar" required>
                                @error('zip_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Format: ZIP / RAR (berisi PDF/JPG/PNG). Maks: 500MB</small>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" id="btnUpload">
                                <i class="mdi mdi-upload me-1"></i> Upload & Proses
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Riwayat Upload -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-history"></i> Riwayat Upload
                </h4>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="40">#</th>
                                <th>Nama Pengupload</th>
                                <th>Nama File</th>
                                <th class="text-center">Jumlah Dokumen</th>
                                <th class="text-center">Status</th>
                                <th>Tanggal Unggah</th>
                                <th class="text-center" width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($uploads as $index => $upload)
                            <tr>
                                <td>{{ $uploads->firstItem() + $index }}</td>
                                <td><strong>{{ $upload->nama_pengupload }}</strong></td>
                                <td>
                                    <small class="text-muted">{{ $upload->zip_filename }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-primary">{{ number_format($upload->jumlah_dokumen) }}</span>
                                </td>
                                <td class="text-center">
                                    @if($upload->status === 'completed')
                                        <span class="badge badge-success"><i class="mdi mdi-check-circle"></i> Selesai</span>
                                    @elseif($upload->status === 'extracting')
                                        <span class="badge badge-warning"><i class="mdi mdi-progress-clock"></i> Proses</span>
                                    @elseif($upload->status === 'pending')
                                        <span class="badge badge-secondary"><i class="mdi mdi-clock-outline"></i> Menunggu</span>
                                    @else
                                        <span class="badge badge-danger"><i class="mdi mdi-alert-circle"></i> Gagal</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($upload->tanggal_unggah)->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('arsip-pensiun.show', $upload->id) }}"
                                           class="btn btn-info btn-sm" title="Lihat Detail">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        @if($upload->status === 'completed')
                                        <a href="{{ route('arsip-pensiun.download-all', $upload->id) }}"
                                           class="btn btn-success btn-sm" title="Download Semua">
                                            <i class="mdi mdi-download"></i>
                                        </a>
                                        @endif
                                        <form action="{{ route('arsip-pensiun.destroy', $upload->id) }}" method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus data ini beserta semua filenya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="mdi mdi-archive-outline" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2">Belum ada data upload</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($uploads->hasPages())
                <div class="mt-3">
                    {{ $uploads->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnUpload');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengupload...';
});
</script>
@endpush
