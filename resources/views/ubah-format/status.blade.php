@extends('layouts.app')

@section('title', 'Status Konversi CSV ke Excel')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">
                    <i class="mdi mdi-file-excel-outline"></i> Status Konversi CSV ke Excel
                </h4>

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-8 offset-md-2">
                        <!-- File Info Card -->
                        <div class="card border-primary mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="mdi mdi-file-document"></i> Informasi File</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Nama File:</dt>
                                    <dd class="col-sm-8"><strong>{{ $job->csv_filename }}</strong></dd>

                                    <dt class="col-sm-4">Waktu Upload:</dt>
                                    <dd class="col-sm-8">{{ \Carbon\Carbon::parse($job->created_at)->format('d M Y, H:i:s') }}</dd>

                                    <dt class="col-sm-4">Job ID:</dt>
                                    <dd class="col-sm-8"><code>#{{ $job->id }}</code></dd>
                                </dl>
                            </div>
                        </div>

                        <!-- Status Card -->
                        <div class="card" id="statusCard">
                            <div class="card-header" id="statusHeader">
                                <h5 class="mb-0"><i class="mdi mdi-progress-clock"></i> Status Processing</h5>
                            </div>
                            <div class="card-body text-center">
                                <!-- Status Badge -->
                                <div class="mb-4">
                                    <span class="badge badge-pill" id="statusBadge" style="font-size: 1.2rem; padding: 10px 20px;">
                                        <span id="statusText">{{ ucfirst($job->status) }}</span>
                                    </span>
                                </div>

                                <!-- Progress Spinner (shown when processing) -->
                                <div id="processingSpinner" style="display: none;">
                                    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                                        <span class="visually-hidden">Processing...</span>
                                    </div>
                                </div>

                                <!-- Success Icon (shown when completed) -->
                                <div id="successIcon" style="display: none;">
                                    <i class="mdi mdi-check-circle text-success" style="font-size: 5rem;"></i>
                                </div>

                                <!-- Error Icon (shown when failed) -->
                                <div id="errorIcon" style="display: none;">
                                    <i class="mdi mdi-alert-circle text-danger" style="font-size: 5rem;"></i>
                                </div>

                                <!-- Status Message -->
                                <div class="mt-3">
                                    <p class="lead" id="statusMessage">{{ $job->message }}</p>
                                </div>

                                <!-- Download Button (shown when completed) -->
                                <div id="downloadSection" style="display: none;" class="mt-4">
                                    <a href="#" id="downloadButton" class="btn btn-success btn-lg">
                                        <i class="mdi mdi-download"></i> Download Excel
                                    </a>
                                </div>

                                <!-- Back Button -->
                                <div class="mt-3">
                                    <a href="{{ url('/ubah-format') }}" class="btn btn-secondary">
                                        <i class="mdi mdi-arrow-left"></i> Kembali
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Processing Info -->
                        <div class="alert alert-info mt-3" id="processingInfo">
                            <i class="mdi mdi-information"></i>
                            <strong>Catatan:</strong> Proses konversi file besar dapat memakan waktu beberapa menit.
                            Halaman ini akan otomatis refresh setiap 3 detik untuk menampilkan status terbaru.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let jobId = {{ $job->id }};
let checkInterval;

// Function to check status
function checkStatus() {
    fetch(`{{ url('/ubah-format/check-status') }}/${jobId}`)
        .then(response => response.json())
        .then(data => {
            updateUI(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Function to update UI based on status
function updateUI(data) {
    const status = data.status;
    const message = data.message;
    const outputFile = data.output_file;

    // Update status text and badge
    document.getElementById('statusText').textContent = ucfirst(status);
    document.getElementById('statusMessage').textContent = message;

    // Hide all icons first
    document.getElementById('processingSpinner').style.display = 'none';
    document.getElementById('successIcon').style.display = 'none';
    document.getElementById('errorIcon').style.display = 'none';
    document.getElementById('downloadSection').style.display = 'none';

    const statusBadge = document.getElementById('statusBadge');
    const statusCard = document.getElementById('statusCard');
    const statusHeader = document.getElementById('statusHeader');

    // Update UI based on status
    if (status === 'pending') {
        statusBadge.className = 'badge badge-pill bg-secondary';
        statusCard.className = 'card border-secondary';
        statusHeader.className = 'card-header bg-secondary text-white';
        document.getElementById('processingInfo').style.display = 'block';
    } else if (status === 'processing') {
        statusBadge.className = 'badge badge-pill bg-primary';
        statusCard.className = 'card border-primary';
        statusHeader.className = 'card-header bg-primary text-white';
        document.getElementById('processingSpinner').style.display = 'block';
        document.getElementById('processingInfo').style.display = 'block';
    } else if (status === 'completed') {
        statusBadge.className = 'badge badge-pill bg-success';
        statusCard.className = 'card border-success';
        statusHeader.className = 'card-header bg-success text-white';
        document.getElementById('successIcon').style.display = 'block';
        document.getElementById('processingInfo').style.display = 'none';

        // Show download button
        if (outputFile) {
            const downloadButton = document.getElementById('downloadButton');
            downloadButton.href = `{{ url('/ubah-format/download') }}/${outputFile}`;
            document.getElementById('downloadSection').style.display = 'block';
        }

        // Stop checking
        clearInterval(checkInterval);
    } else if (status === 'failed') {
        statusBadge.className = 'badge badge-pill bg-danger';
        statusCard.className = 'card border-danger';
        statusHeader.className = 'card-header bg-danger text-white';
        document.getElementById('errorIcon').style.display = 'block';
        document.getElementById('processingInfo').style.display = 'none';

        // Stop checking
        clearInterval(checkInterval);
    }
}

// Helper function to capitalize first letter
function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initial status check
    checkStatus();

    // Set interval to check status every 3 seconds
    const currentStatus = '{{ $job->status }}';
    if (currentStatus === 'pending' || currentStatus === 'processing') {
        checkInterval = setInterval(checkStatus, 3000);
    } else {
        // Update UI immediately if already completed or failed
        updateUI({
            status: currentStatus,
            message: '{{ $job->message }}',
            output_file: '{{ $job->output_file }}'
        });
    }
});
</script>
@endsection
