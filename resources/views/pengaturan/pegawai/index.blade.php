@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Kelola Pengguna</h4>
                    <div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari pegawai...">
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Golongan</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pegawai as $index => $p)
                                <tr>
                                    <td>{{ $pegawai->firstItem() + $index }}</td>
                                    <td>{{ $p->nip }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($p->photo)
                                                <img src="{{ asset('uploads/photos/' . $p->photo) }}" class="rounded-circle me-2" width="32" height="32">
                                            @else
                                                <div class="rounded-circle bg-primary text-white me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                    {{ strtoupper(substr($p->nama, 0, 1)) }}
                                                </div>
                                            @endif
                                            <span>{{ $p->nama }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $p->jabatan }}</td>
                                    <td>{{ $p->golongan }}</td>
                                    <td>
                                        @if($p->role)
                                            <span class="badge badge-{{ $p->role->name == 'Admin' ? 'danger' : ($p->role->name == 'Pimpinan' ? 'warning' : 'info') }}">
                                                {{ $p->role->name }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-status" type="checkbox"
                                                   data-id="{{ $p->id }}"
                                                   {{ $p->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label status-label-{{ $p->id }}">
                                                {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('pegawai.edit', $p->id) }}" class="btn btn-sm btn-primary">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Tidak ada data pegawai</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Menampilkan {{ $pegawai->firstItem() }} - {{ $pegawai->lastItem() }} dari {{ $pegawai->total() }} data
                    </div>
                    <div>
                        {{ $pegawai->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.pagination {
    margin-bottom: 0;
}
.pagination .page-link {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-color: #dee2e6;
    color: #6c757d;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.pagination .page-link:hover {
    background-color: #e9ecef;
    color: #0d6efd;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Active Status
    document.querySelectorAll('.toggle-status').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const pegawaiId = this.dataset.id;
            const isChecked = this.checked;

            fetch(`/pengaturan/pegawai/${pegawaiId}/toggle-active`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const label = document.querySelector(`.status-label-${pegawaiId}`);
                    label.textContent = data.is_active ? 'Aktif' : 'Nonaktif';

                    // Show toast notification
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                    alertDiv.style.zIndex = '9999';
                    alertDiv.innerHTML = `
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alertDiv);
                    setTimeout(() => alertDiv.remove(), 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isChecked; // Revert toggle
            });
        });
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
});
</script>
@endpush
