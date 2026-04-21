@extends('layouts.app')

@section('title', 'Semua Dokumen Arsip Pensiun')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h4 class="card-title mb-1">
                            <i class="mdi mdi-file-multiple"></i> Semua Dokumen Arsip Pensiun
                        </h4>
                        <small class="text-muted">Total: <strong id="totalCount">{{ number_format($totalFiles) }}</strong> dokumen dari seluruh upload</small>
                    </div>
                    <a href="{{ route('arsip-pensiun.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                            <input type="text" class="form-control" id="searchInput"
                                   placeholder="Cari nama file..." autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" style="display:none;">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-8 text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-success" id="btnDownloadSelected" disabled onclick="downloadSelected()">
                                <i class="mdi mdi-download me-1"></i> Download Terpilih (<span id="selectedCountBtn">0</span>)
                            </button>
                            <button type="button" class="btn btn-primary" onclick="downloadPage()">
                                <i class="mdi mdi-file-download me-1"></i> Download Halaman Ini
                            </button>
                            <button type="button" class="btn btn-warning" onclick="downloadAllFiles()">
                                <i class="mdi mdi-download-multiple me-1"></i> Download Semua
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr class="table-light">
                                <th width="35" class="text-center">
                                    <input class="form-check-input" type="checkbox" id="selectAll" title="Pilih semua">
                                </th>
                                <th width="45">#</th>
                                <th>Nama File</th>
                                <th width="150">Pengupload</th>
                                <th class="text-center" width="60">Tipe</th>
                                <th class="text-center" width="80">Ukuran</th>
                                <th class="text-center" width="90">Tanggal</th>
                                <th class="text-center" width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    <span class="ms-2 text-muted">Memuat dokumen...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top" id="paginationBar">
                    <small class="text-muted" id="pageInfo">Halaman 1</small>
                    <div id="paginationButtons"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for download -->
<form id="downloadForm" method="POST" action="{{ route('arsip-pensiun.download-selected') }}" style="display:none;">
    @csrf
    <div id="downloadFormInputs"></div>
</form>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentSearch = '';
let searchTimeout = null;

// Init
document.addEventListener('DOMContentLoaded', () => loadFiles());

// Search with debounce
const searchInput = document.getElementById('searchInput');
const btnClear = document.getElementById('btnClearSearch');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    btnClear.style.display = this.value ? '' : 'none';
    searchTimeout = setTimeout(() => {
        currentSearch = this.value.trim();
        currentPage = 1;
        loadFiles();
    }, 300);
});

searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimeout);
        currentSearch = this.value.trim();
        currentPage = 1;
        loadFiles();
    }
});

btnClear.addEventListener('click', function() {
    searchInput.value = '';
    btnClear.style.display = 'none';
    currentSearch = '';
    currentPage = 1;
    loadFiles();
    searchInput.focus();
});

// Select all
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.file-cb').forEach(cb => cb.checked = this.checked);
    updateCount();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('file-cb')) {
        const all = document.querySelectorAll('.file-cb');
        const checked = document.querySelectorAll('.file-cb:checked');
        document.getElementById('selectAll').checked = all.length > 0 && all.length === checked.length;
        updateCount();
    }
});

function updateCount() {
    const n = document.querySelectorAll('.file-cb:checked').length;
    document.getElementById('selectedCountBtn').textContent = n;
    document.getElementById('btnDownloadSelected').disabled = n === 0;
}

function loadFiles() {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div><span class="ms-2 text-muted">Memuat...</span></td></tr>';

    let url = '{{ route("arsip-pensiun.api-files") }}?page=' + currentPage;
    if (currentSearch) url += '&search=' + encodeURIComponent(currentSearch);

    fetch(url)
        .then(r => r.json())
        .then(result => {
            document.getElementById('selectAll').checked = false;
            updateCount();

            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="mdi mdi-file-search-outline" style="font-size:2rem;"></i><p class="mb-0 mt-2">' + (currentSearch ? 'Tidak ditemukan file "' + currentSearch + '"' : 'Belum ada dokumen') + '</p></td></tr>';
                document.getElementById('pageInfo').textContent = '';
                document.getElementById('paginationButtons').innerHTML = '';
                return;
            }

            let html = '';
            result.data.forEach(file => {
                const icon = file.is_pdf ? 'mdi-file-pdf text-danger' : 'mdi-file-image text-info';
                const badgeClass = file.is_pdf ? 'badge-danger' : 'badge-info';
                html += `<tr>
                    <td class="text-center"><input class="form-check-input file-cb" type="checkbox" value="${file.id}" data-uid="${file.upload_id}"></td>
                    <td class="text-muted small">${file.no}</td>
                    <td><i class="mdi ${icon} me-1"></i><span class="small">${file.original_filename}</span></td>
                    <td><small class="text-muted">${file.nama_pengupload}</small></td>
                    <td class="text-center"><span class="badge ${badgeClass}" style="font-size:.65rem;">${file.ext}</span></td>
                    <td class="text-center"><small>${file.size}</small></td>
                    <td class="text-center"><small>${file.date}</small></td>
                    <td class="text-center"><a href="${file.download_url}" class="text-primary" title="Download"><i class="mdi mdi-download"></i></a></td>
                </tr>`;
            });
            tbody.innerHTML = html;

            // Pagination
            document.getElementById('pageInfo').textContent = 'Halaman ' + result.current_page;
            let pagHtml = '';
            if (result.has_prev) {
                pagHtml += `<button class="btn btn-sm btn-outline-secondary me-1" onclick="goPage(${result.current_page - 1})"><i class="mdi mdi-chevron-left"></i> Sebelumnya</button>`;
            }
            if (result.has_more) {
                pagHtml += `<button class="btn btn-sm btn-outline-secondary" onclick="goPage(${result.current_page + 1})">Berikutnya <i class="mdi mdi-chevron-right"></i></button>`;
            }
            document.getElementById('paginationButtons').innerHTML = pagHtml;
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4"><i class="mdi mdi-alert-circle"></i> Gagal memuat data</td></tr>';
        });
}

function goPage(page) {
    currentPage = page;
    loadFiles();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function downloadSelected() {
    const checked = document.querySelectorAll('.file-cb:checked');
    if (!checked.length) return;
    submitDownloadForm(checked);
}

function downloadPage() {
    const all = document.querySelectorAll('.file-cb');
    if (!all.length) return;
    submitDownloadForm(all);
}

function submitDownloadForm(checkboxes) {
    const form = document.getElementById('downloadForm');
    const inputs = document.getElementById('downloadFormInputs');
    inputs.innerHTML = '';
    checkboxes.forEach(cb => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'file_ids[]';
        inp.value = cb.value;
        inputs.appendChild(inp);
    });
    form.submit();
}

function downloadAllFiles() {
    if (!confirm('Download semua {{ number_format($totalFiles) }} file sebagai ZIP? Proses ini mungkin memakan waktu.')) return;
    window.location.href = '{{ route("arsip-pensiun.download-all-files") }}';
}
</script>
@endpush
