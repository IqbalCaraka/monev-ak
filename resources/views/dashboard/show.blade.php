@extends('layouts.app')

@section('title', 'DMS Upload Detail')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">Upload: {{ $upload->filename }}</h4>
                        <p class="text-muted mb-0">{{ $upload->upload_date->format('d F Y H:i:s') }}</p>
                    </div>
                    <div>
                        <form action="{{ route('dms.calculate-all', $upload->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('Calculate all instansi? This may take several minutes.')">
                                <i class="mdi mdi-calculator"></i> Calculate All Instansi
                            </button>
                        </form>
                        <a href="{{ route('dashboard.dms') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6>Total Records</h6>
                                <h3>{{ number_format($upload->total_records) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Total Instansi</h6>
                                <h3>{{ $instansiList->total() }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Instansi List</h4>
                    <div class="d-flex gap-2">
                        <input type="text" id="searchInstansi" class="form-control form-control-sm" placeholder="Search instansi..." style="width: 250px;">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Instansi</th>
                                <th>Total PNS</th>
                                <th>Avg Calculated</th>
                                <th>Avg System</th>
                                <th>Status Kelengkapan</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($instansiList as $instansi)
                                @php
                                    // Badge untuk status kelengkapan instansi
                                    $kelengkapanBadge = '';
                                    switch($instansi->status_kelengkapan) {
                                        case 'Sangat Lengkap':
                                            $kelengkapanBadge = 'bg-success';
                                            break;
                                        case 'Lengkap':
                                            $kelengkapanBadge = 'bg-primary';
                                            break;
                                        case 'Cukup Lengkap':
                                            $kelengkapanBadge = 'bg-warning';
                                            break;
                                        case 'Kurang Lengkap':
                                            $kelengkapanBadge = 'bg-danger';
                                            break;
                                        default:
                                            $kelengkapanBadge = 'bg-secondary';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $instansi->instansi_nama }}</td>
                                    <td>{{ number_format($instansi->total_pns) }}</td>
                                    <td>
                                        @if($instansi->skor_instansi_calculated_system)
                                            <span class="badge bg-primary">{{ number_format($instansi->skor_instansi_calculated_system, 2) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($instansi->skor_instansi_calculated_csv)
                                            <span class="badge bg-secondary">{{ number_format($instansi->skor_instansi_calculated_csv, 2) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($instansi->status_kelengkapan)
                                            <span class="badge {{ $kelengkapanBadge }}">{{ $instansi->status_kelengkapan }}</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($instansi->calculation_status === 'completed')
                                            <span class="badge bg-success">Calculated</span>
                                        @elseif($instansi->calculation_status === 'calculating')
                                            <span class="badge bg-warning">Calculating...</span>
                                        @else
                                            <span class="badge bg-secondary">Not Calculated</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($instansi->calculation_status !== 'calculating')
                                            <button class="btn btn-sm btn-primary btn-calculate"
                                                    data-upload-id="{{ $upload->id }}"
                                                    data-instansi-id="{{ $instansi->instansi_id }}">
                                                <i class="mdi mdi-calculator"></i> Calculate
                                            </button>
                                        @endif
                                        @if($instansi->calculation_status === 'completed')
                                            <a href="{{ route('dms.instansi-detail', [$upload->id, $instansi->instansi_id]) }}"
                                               class="btn btn-sm btn-info">
                                                <i class="mdi mdi-eye"></i> Detail
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    {{ $instansiList->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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

@push('scripts')
<script>
// Polling untuk monitor status calculating
let pollingIntervals = {};

function startPollingCalculationStatus(uploadId, instansiId, row) {
    const intervalId = setInterval(function() {
        // Reload untuk update status
        location.reload();
    }, 3000); // Poll setiap 3 detik

    pollingIntervals[`${uploadId}_${instansiId}`] = intervalId;
}

function stopPolling(uploadId, instansiId) {
    const key = `${uploadId}_${instansiId}`;
    if (pollingIntervals[key]) {
        clearInterval(pollingIntervals[key]);
        delete pollingIntervals[key];
    }
}

console.log('Calculate buttons found:', document.querySelectorAll('.btn-calculate').length);

document.querySelectorAll('.btn-calculate').forEach(btn => {
    btn.addEventListener('click', function() {
        const uploadId = this.dataset.uploadId;
        const instansiId = this.dataset.instansiId;
        const row = this.closest('tr');

        console.log('Calculate clicked:', {uploadId, instansiId});

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Calculating...';

        fetch('{{ route("dms.calculate-instansi") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                upload_id: uploadId,
                instansi_id: instansiId
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            // Start polling untuk monitor status
            startPollingCalculationStatus(uploadId, instansiId, row);
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error: ' + error);
            this.disabled = false;
            this.innerHTML = '<i class="mdi mdi-calculator"></i> Calculate';
        });
    });
});

// Auto-reload jika ada status "calculating"
window.addEventListener('DOMContentLoaded', function() {
    const hasCalculating = document.querySelector('.badge.bg-warning');
    if (hasCalculating && hasCalculating.textContent.includes('Calculating')) {
        setTimeout(function() {
            location.reload();
        }, 3000);
    }
});

// Search Instansi
document.getElementById('searchInstansi').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('.table tbody tr');

    tableRows.forEach(row => {
        const instansiName = row.querySelector('td:first-child').textContent.toLowerCase();
        if (instansiName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
@endpush