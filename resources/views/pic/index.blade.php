@extends('layouts.app')

@section('title', 'Kelola PIC')

@section('content')

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

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Kelola PIC DMS</h4>
                        <p class="text-muted mb-0">Person In Charge Document Management System</p>
                    </div>
                    <a href="{{ route('pic.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Tambah PIC DMS
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th>Ketua PIC DMS</th>
                                <th class="text-center">Anggota Tim</th>
                                <th class="text-center">Instansi</th>
                                <th class="text-center" width="100">Status</th>
                                <th class="text-center" width="200">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pics as $index => $pic)
                                <tr>
                                    <td>{{ $pics->firstItem() + $index }}</td>
                                    <td>
                                        @if($pic->ketua)
                                            <div><strong>{{ $pic->ketua->nama }}</strong></div>
                                            <small class="text-muted">NIP: {{ $pic->ketua->nip }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $pic->anggota_count }} orang</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">{{ $pic->instansi_count }} instansi</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input toggle-active"
                                                   type="checkbox"
                                                   data-id="{{ $pic->id }}"
                                                   {{ $pic->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('pic.show', $pic->id) }}"
                                           class="btn btn-sm btn-outline-info"
                                           title="Lihat Detail">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('pic.edit', $pic->id) }}"
                                           class="btn btn-sm btn-outline-warning"
                                           title="Edit">
                                            <i class="mdi mdi-pencil"></i>
                                        </a>
                                        <form action="{{ route('pic.destroy', $pic->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus PIC ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Hapus">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada data PIC DMS. <a href="{{ route('pic.create') }}">Tambah PIC DMS</a> pertama Anda!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($pics->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ $pics->firstItem() }} - {{ $pics->lastItem() }} dari {{ $pics->total() }} data
                    </div>
                    <div>
                        {{ $pics->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Active Status
    document.querySelectorAll('.toggle-active').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const picId = this.dataset.id;
            const isChecked = this.checked;

            fetch(`/pengaturan/pic/${picId}/toggle-active`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isChecked; // Revert if failed
                alert('Gagal mengubah status!');
            });
        });
    });
});
</script>

@endsection
