@extends('layouts.app')

@section('title', 'Detail Instansi DMS')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title mb-0">
                            @if($instansiScore)
                                {{ $instansiScore->instansi_nama }}
                            @else
                                Detail Instansi
                            @endif
                        </h4>
                        <p class="text-muted mb-0">Upload: {{ $upload->filename }}</p>
                    </div>
                    <div>
                        <a href="{{ route('dms.show', $upload->id) }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                @if($instansiScore)
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6>Total PNS</h6>
                                    <h3>{{ number_format($instansiScore->total_pns) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6>Avg Score (Calculated)</h6>
                                    <h3>{{ number_format($instansiScore->skor_instansi_calculated_system, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6>Avg Score (CSV)</h6>
                                    <h3>{{ number_format($instansiScore->skor_instansi_calculated_csv, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6>Calculated At</h6>
                                    <h3>{{ $instansiScore->calculated_at->format('H:i') }}</h3>
                                    <small>{{ $instansiScore->calculated_at->format('d M Y') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h6 class="text-primary">Score Distribution</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>80-100 (Sangat Baik)</td>
                                            <td class="text-end"><strong>{{ $instansiScore->count_80_100 }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>60-79.99 (Baik)</td>
                                            <td class="text-end"><strong>{{ $instansiScore->count_60_79 }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>40-59.99 (Cukup)</td>
                                            <td class="text-end"><strong>{{ $instansiScore->count_40_59 }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>0-39.99 (Kurang)</td>
                                            <td class="text-end"><strong>{{ $instansiScore->count_0_39 }}</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert"></i> Instansi ini belum dihitung. Silakan klik tombol "Calculate" di halaman sebelumnya.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Daftar PNS</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Skor CSV</th>
                                <th>Skor Calculated</th>
                                <th>Selisih</th>
                                <th>Kategori Skor</th>
                                <th>Status Kelengkapan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pnsList as $pns)
                                @php
                                    $selisih = $pns->skor_calculated - $pns->skor_csv;
                                    $kategori = '';
                                    $badgeClass = '';
                                    if ($pns->skor_calculated >= 80) {
                                        $kategori = 'Sangat Baik';
                                        $badgeClass = 'bg-success';
                                    } elseif ($pns->skor_calculated >= 60) {
                                        $kategori = 'Baik';
                                        $badgeClass = 'bg-info';
                                    } elseif ($pns->skor_calculated >= 40) {
                                        $kategori = 'Cukup';
                                        $badgeClass = 'bg-warning';
                                    } else {
                                        $kategori = 'Kurang';
                                        $badgeClass = 'bg-danger';
                                    }

                                    // Badge untuk status kelengkapan
                                    $kelengkapanBadge = '';
                                    switch($pns->status_kelengkapan) {
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
                                    <td><small>{{ $pns->nip }}</small></td>
                                    <td>{{ $pns->nama }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $pns->status_cpns_pns }}</span>
                                    </td>
                                    <td>{{ number_format($pns->skor_csv, 2) }}</td>
                                    <td><strong>{{ number_format($pns->skor_calculated, 2) }}</strong></td>
                                    <td>
                                        @if($selisih > 0)
                                            <span class="text-success">+{{ number_format($selisih, 2) }}</span>
                                        @elseif($selisih < 0)
                                            <span class="text-danger">{{ number_format($selisih, 2) }}</span>
                                        @else
                                            <span class="text-muted">0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $kategori }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $kelengkapanBadge }}">{{ $pns->status_kelengkapan }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info btn-detail-pns"
                                                data-nip="{{ $pns->nip }}"
                                                data-nama="{{ $pns->nama }}"
                                                data-status="{{ $pns->status_cpns_pns }}"
                                                data-skor-csv="{{ $pns->skor_csv }}"
                                                data-skor-calculated="{{ $pns->skor_calculated }}"
                                                data-status-arsip="{{ htmlspecialchars($pns->status_arsip) }}">
                                            <i class="mdi mdi-file-document-outline"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Tidak ada data PNS</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $pnsList->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- Modal Detail PNS -->
<div class="modal fade" id="modalDetailPns" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Skor DMS PNS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>Informasi PNS</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="150">NIP</td>
                            <td><strong id="detail-nip"></strong></td>
                        </tr>
                        <tr>
                            <td>Nama</td>
                            <td><strong id="detail-nama"></strong></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td><span id="detail-status" class="badge bg-secondary"></span></td>
                        </tr>
                    </table>
                </div>

                <div class="mb-3">
                    <h6>Perbandingan Skor</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Skor dari CSV</td>
                            <td class="text-end"><strong id="detail-skor-csv"></strong></td>
                        </tr>
                        <tr>
                            <td>Skor Calculated (Sistem)</td>
                            <td class="text-end"><strong id="detail-skor-calculated"></strong></td>
                        </tr>
                        <tr class="table-info">
                            <td><strong>Selisih</strong></td>
                            <td class="text-end"><strong id="detail-selisih"></strong></td>
                        </tr>
                    </table>
                </div>

                <div class="mb-3">
                    <h6>Breakdown Perhitungan Skor</h6>
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td>Arsip Utama (Max 90 poin)</td>
                                    <td class="text-end"><strong id="detail-skor-utama" class="text-primary"></strong></td>
                                </tr>
                                <tr>
                                    <td>Arsip Kondisional (Max 10 poin)</td>
                                    <td class="text-end"><strong id="detail-skor-kondisional" class="text-success"></strong></td>
                                </tr>
                                <tr class="table-primary">
                                    <td><strong>Total Skor</strong></td>
                                    <td class="text-end"><strong id="detail-skor-total"></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h6>Status Dokumen Arsip Utama</h6>
                    <div id="detail-arsip-utama"></div>
                </div>

                <div class="mb-3">
                    <h6>Status Dokumen Arsip Kondisional</h6>
                    <div id="detail-arsip-kondisional"></div>
                </div>

                <div class="mb-3">
                    <h6>Kekurangan Dokumen</h6>
                    <div id="detail-kekurangan" class="alert alert-warning"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Fungsi untuk hitung skor (sama seperti di DmsScoreCalculator.php)
function hitungSkorDetail(statusArsip, statusCpnsPns) {
    let skorUtama = 0;
    let skorKondisional = 0;
    let breakdown = {
        utama: {},
        kondisional: {}
    };

    // 1. DRH (Fixed 7.5)
    skorUtama += 7.5;
    breakdown.utama.drh = 7.5;

    // 2. Data Utama - Inisialisasi default
    breakdown.utama.d2np = 0;
    breakdown.utama.cpns = 0;
    breakdown.utama.pns = 0;

    if (statusArsip.data_utama) {
        breakdown.utama.d2np = statusArsip.data_utama.d2np == 1 ? 7.5 : 0;
        breakdown.utama.cpns = statusArsip.data_utama.cpns == 1 ? 7.5 : 0;
        breakdown.utama.pns = (statusCpnsPns === 'C' || statusArsip.data_utama.pns == 1) ? 7.5 : 0;

        skorUtama += breakdown.utama.d2np + breakdown.utama.cpns + breakdown.utama.pns;
    }

    // 3. Riwayat Pendidikan
    if (statusArsip.riwayat_pendidikan && statusArsip.riwayat_pendidikan.length > 0) {
        breakdown.utama.pendidikan = 15;
        skorUtama += 15;
    } else {
        breakdown.utama.pendidikan = 0;
    }

    // 4. Riwayat Golongan
    if (statusArsip.riwayat_golongan && statusArsip.riwayat_golongan.length > 0) {
        breakdown.utama.golongan = 15;
        skorUtama += 15;
    } else {
        breakdown.utama.golongan = 0;
    }

    // 5. Riwayat Jabatan
    if (statusArsip.riwayat_jabatan && statusArsip.riwayat_jabatan.length > 0) {
        breakdown.utama.jabatan = 15;
        skorUtama += 15;
    } else {
        breakdown.utama.jabatan = 0;
    }

    // 6. Riwayat Diklat
    if (statusArsip.riwayat_diklat && statusArsip.riwayat_diklat.length > 0) {
        breakdown.utama.diklat = 15;
        skorUtama += 15;
    } else {
        breakdown.utama.diklat = 0;
    }

    // 7. Kondisional (max 10)
    let countKondisional = 0;
    breakdown.kondisional = {
        angka_kredit: 0,
        pindah_instansi: 0,
        pmk: 0,
        penghargaan: 0,
        cltn: 0,
        skp22: 0
    };

    if (statusArsip.riwayat_angkakredit && statusArsip.riwayat_angkakredit.length > 0) {
        countKondisional++;
        breakdown.kondisional.angka_kredit = 1;
    }
    if (statusArsip.riwayat_pindahinstansi && statusArsip.riwayat_pindahinstansi.length > 0) {
        countKondisional++;
        breakdown.kondisional.pindah_instansi = 1;
    }
    if (statusArsip.riwayat_pmk && statusArsip.riwayat_pmk.length > 0) {
        countKondisional++;
        breakdown.kondisional.pmk = 1;
    }
    if (statusArsip.riwayat_penghargaan && statusArsip.riwayat_penghargaan.length > 0) {
        countKondisional++;
        breakdown.kondisional.penghargaan = 1;
    }
    if (statusArsip.riwayat_cltn && statusArsip.riwayat_cltn.length > 0) {
        countKondisional++;
        breakdown.kondisional.cltn = 1;
    }
    if (statusArsip.riwayat_skp && statusArsip.riwayat_skp.some(skp => skp.tahun == 2022)) {
        countKondisional++;
        breakdown.kondisional.skp22 = 1;
    }

    skorKondisional = (countKondisional / 6) * 10;

    return {
        skorUtama: parseFloat(skorUtama.toFixed(2)),
        skorKondisional: parseFloat(skorKondisional.toFixed(2)),
        skorTotal: parseFloat((skorUtama + skorKondisional).toFixed(2)),
        breakdown: breakdown
    };
}

document.querySelectorAll('.btn-detail-pns').forEach(btn => {
    btn.addEventListener('click', function() {
        const nip = this.dataset.nip;
        const nama = this.dataset.nama;
        const status = this.dataset.status;
        const skorCsv = parseFloat(this.dataset.skorCsv);
        const skorCalculated = parseFloat(this.dataset.skorCalculated);
        const statusArsipJson = this.dataset.statusArsip;

        // Parse status arsip
        let statusArsip = {};
        try {
            statusArsip = JSON.parse(statusArsipJson);
        } catch(e) {
            console.error('Error parsing status arsip:', e);
            statusArsip = {};
        }

        // Hitung skor detail
        const hasilHitung = hitungSkorDetail(statusArsip, status);

        // Populate modal
        document.getElementById('detail-nip').textContent = nip;
        document.getElementById('detail-nama').textContent = nama;
        document.getElementById('detail-status').textContent = status;
        document.getElementById('detail-skor-csv').textContent = skorCsv.toFixed(2);
        document.getElementById('detail-skor-calculated').textContent = skorCalculated.toFixed(2);

        const selisih = skorCalculated - skorCsv;
        const selisihEl = document.getElementById('detail-selisih');
        if (selisih > 0) {
            selisihEl.innerHTML = '<span class="text-success">+' + selisih.toFixed(2) + '</span>';
        } else if (selisih < 0) {
            selisihEl.innerHTML = '<span class="text-danger">' + selisih.toFixed(2) + '</span>';
        } else {
            selisihEl.innerHTML = '<span class="text-muted">0.00</span>';
        }

        document.getElementById('detail-skor-utama').textContent = hasilHitung.skorUtama.toFixed(2);
        document.getElementById('detail-skor-kondisional').textContent = hasilHitung.skorKondisional.toFixed(2);
        document.getElementById('detail-skor-total').textContent = hasilHitung.skorTotal.toFixed(2);

        // Arsip Utama - dengan safe accessor
        const bd = hasilHitung.breakdown.utama;
        const arsipUtamaHtml = `
            <table class="table table-sm table-bordered">
                <tr>
                    <td>DRH</td>
                    <td class="text-center">${(bd.drh || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.drh || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>D2NP (Data Utama)</td>
                    <td class="text-center">${(bd.d2np || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.d2np || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>SK CPNS</td>
                    <td class="text-center">${(bd.cpns || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.cpns || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>SK PNS</td>
                    <td class="text-center">${(bd.pns || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.pns || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Riwayat Pendidikan</td>
                    <td class="text-center">${(bd.pendidikan || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.pendidikan || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Riwayat Golongan</td>
                    <td class="text-center">${(bd.golongan || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.golongan || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Riwayat Jabatan</td>
                    <td class="text-center">${(bd.jabatan || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.jabatan || 0).toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Riwayat Diklat</td>
                    <td class="text-center">${(bd.diklat || 0) > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                    <td class="text-end">${(bd.diklat || 0).toFixed(2)}</td>
                </tr>
                <tr class="table-primary">
                    <td colspan="2"><strong>Total Arsip Utama</strong></td>
                    <td class="text-end"><strong>${hasilHitung.skorUtama.toFixed(2)}</strong></td>
                </tr>
            </table>
        `;
        document.getElementById('detail-arsip-utama').innerHTML = arsipUtamaHtml;

        // Arsip Kondisional
        const arsipKondisionalHtml = `
            <table class="table table-sm table-bordered">
                <tr>
                    <td>Angka Kredit</td>
                    <td class="text-center">${hasilHitung.breakdown.kondisional.angka_kredit > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                </tr>
                <tr>
                    <td>Pindah Instansi</td>
                    <td class="text-center">${hasilHitung.breakdown.kondisional.pindah_instansi > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                </tr>
                <tr>
                    <td>PMK</td>
                    <td class="text-center">${hasilHitung.breakdown.kondisional.pmk > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                </tr>
                <tr>
                    <td>Penghargaan</td>
                    <td class="text-center">${hasilHitung.breakdown.kondisional.penghargaan > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                </tr>
                <tr>
                    <td>CLTN</td>
                    <td class="text-center">${hasilHitung.breakdown.kondisional.cltn > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                </tr>
                <tr>
                    <td>SKP 2022</td>
                    <td class="text-center">${hasilHitung.breakdown.kondisional.skp22 > 0 ? '<i class="mdi mdi-check text-success"></i>' : '<i class="mdi mdi-close text-danger"></i>'}</td>
                </tr>
                <tr class="table-success">
                    <td><strong>Total Arsip Kondisional</strong></td>
                    <td class="text-end"><strong>${hasilHitung.skorKondisional.toFixed(2)}</strong></td>
                </tr>
            </table>
        `;
        document.getElementById('detail-arsip-kondisional').innerHTML = arsipKondisionalHtml;

        // Kekurangan - dengan safe accessor
        const kekurangan = [];
        if ((bd.d2np || 0) === 0) kekurangan.push('D2NP (Data Utama)');
        if ((bd.cpns || 0) === 0) kekurangan.push('SK CPNS');
        if ((bd.pns || 0) === 0 && status !== 'C') kekurangan.push('SK PNS');
        if ((bd.pendidikan || 0) === 0) kekurangan.push('Riwayat Pendidikan');
        if ((bd.golongan || 0) === 0) kekurangan.push('Riwayat Golongan');
        if ((bd.jabatan || 0) === 0) kekurangan.push('Riwayat Jabatan');
        if ((bd.diklat || 0) === 0) kekurangan.push('Riwayat Diklat');

        if (kekurangan.length > 0) {
            document.getElementById('detail-kekurangan').innerHTML = '<strong>Dokumen yang belum tersedia:</strong><ul class="mb-0 mt-2">' +
                kekurangan.map(k => '<li>' + k + '</li>').join('') + '</ul>';
        } else {
            document.getElementById('detail-kekurangan').innerHTML = '<i class="mdi mdi-check-circle text-success"></i> Semua dokumen arsip utama sudah lengkap!';
            document.getElementById('detail-kekurangan').classList.remove('alert-warning');
            document.getElementById('detail-kekurangan').classList.add('alert-success');
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('modalDetailPns'));
        modal.show();
    });
});
</script>
@endpush
