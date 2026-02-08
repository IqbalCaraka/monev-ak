@extends('layouts.app')

@section('title', 'Kelola Instansi')

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Kelola Instansi</h4>
                    <div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari instansi...">
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
                                <th>Nama Instansi</th>
                                <th>Jenis Instansi</th>
                                <th>Kantor Regional ID</th>
                                <th>Provinsi ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instansi as $index => $i)
                                <tr>
                                    <td>{{ $instansi->firstItem() + $index }}</td>
                                    <td>
                                        <div>
                                            <strong>{{ $i->nama }}</strong>
                                            @if($i->nama_baru && $i->nama_baru != $i->nama)
                                                <br><small class="text-muted">{{ $i->nama_baru }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($i->jenis_instansi_id)
                                            <span class="badge badge-{{
                                                $i->jenis_instansi_id == 'KEMENT' ? 'primary' :
                                                ($i->jenis_instansi_id == 'LPNK' ? 'info' :
                                                ($i->jenis_instansi_id == 'KAB' ? 'success' :
                                                ($i->jenis_instansi_id == 'KOTA' ? 'warning' : 'secondary')))
                                            }}">
                                                {{ $i->jenis_instansi_id }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($i->kantor_regional_id !== null && $i->kantor_regional_id !== '')
                                            <span class="badge badge-outline-primary">{{ $i->kantor_regional_id }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($i->prov_id && $i->prov_id != '6000')
                                            <span class="badge badge-outline-info">{{ $i->prov_id }}</span>
                                        @else
                                            <span class="text-muted">Pusat</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data instansi</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Menampilkan {{ $instansi->firstItem() }} - {{ $instansi->lastItem() }} dari {{ $instansi->total() }} data
                    </div>
                    <div>
                        {{ $instansi->links('pagination::bootstrap-5') }}
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
.badge-outline-primary {
    border: 1px solid #0d6efd;
    color: #0d6efd;
    background: transparent;
}
.badge-outline-info {
    border: 1px solid #0dcaf0;
    color: #0dcaf0;
    background: transparent;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
