@extends('layouts.app')

@section('title', 'Detail Aktivitas Pegawai')

@section('content')

<!-- Date Filter Form -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-2">
                <form method="GET" action="{{ route('aktivitas-pegawai.show', $pegawai->nip) }}">
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
                                <a href="{{ route('aktivitas-pegawai.show', $pegawai->nip) }}" class="btn btn-sm btn-secondary">
                                    <i class="ti-reload"></i> Reset
                                </a>
                            @endif
                        </div>
                        <div class="col-md-3 text-end">
                            <a href="{{ route('aktivitas-pegawai.index') }}" class="btn btn-sm btn-outline-secondary">
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
                    <h4 class="card-title mb-1">Detail Aktivitas Pegawai</h4>
                    <p class="text-muted mb-0">Detail statistik aktivitas untuk pegawai berikut</p>
                </div>

                <!-- Pegawai Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="row">
                                <div class="col-4 text-muted">NIP</div>
                                <div class="col-8"><strong>{{ $pegawai->nip }}</strong></div>
                            </div>
                        </div>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="row">
                                <div class="col-4 text-muted">Nama</div>
                                <div class="col-8"><strong>{{ $pegawai->nama }}</strong></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="row">
                                <div class="col-4 text-muted">Jabatan</div>
                                <div class="col-8">{{ $pegawai->jabatan ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="row">
                                <div class="col-4 text-muted">Golongan</div>
                                <div class="col-8">{{ $pegawai->golongan ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Aktivitas Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-primary d-flex align-items-center" role="alert">
                            <i class="ti-stats-up icon-lg me-3"></i>
                            <div>
                                <h5 class="mb-0">Total Aktivitas: <strong>{{ number_format($totalAktivitas) }}</strong></h5>
                                <small>Terdiri dari {{ $detailAktivitas->count() }} jenis aktivitas berbeda</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Aktivitas per Kategori -->
                <h5 class="mb-3">Breakdown Aktivitas per Kategori</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th width="50">No</th>
                                <th>Kategori Aktivitas</th>
                                <th class="text-center" width="120">Total</th>
                                <th class="text-center" width="180">Persentase</th>
                                <th class="text-center" width="120">Last Activity</th>
                                <th class="text-center" width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detailAktivitas as $index => $detail)
                                @php
                                    $percentage = $totalAktivitas > 0 ? ($detail->total_aktivitas / $totalAktivitas) * 100 : 0;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $detail->kategori_aktivitas }}</strong>
                                        @if(str_starts_with($detail->kategori_aktivitas, 'Inject'))
                                            <span class="badge badge-warning ms-2">Inject</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary badge-pill">{{ number_format($detail->total_aktivitas) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 20px;">
                                                <div class="progress-bar bg-{{ str_starts_with($detail->kategori_aktivitas, 'Inject') ? 'warning' : 'primary' }}"
                                                     role="progressbar"
                                                     style="width: {{ $percentage }}%"
                                                     aria-valuenow="{{ $percentage }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($percentage, 1) }}%
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $detail->last_activity_at ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('aktivitas-pegawai.detail-kategori', [$pegawai->nip, $detail->kategori_aktivitas]) }}?date_from={{ $dateFrom ?? '' }}&date_to={{ $dateTo ?? '' }}"
                                           class="btn btn-sm btn-outline-info">
                                            <i class="ti-list"></i> Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                <td class="text-center">
                                    <strong class="text-primary">{{ number_format($totalAktivitas) }}</strong>
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Info Note -->
                <div class="alert alert-info mt-4" role="alert">
                    <i class="ti-info-alt me-2"></i>
                    <strong>Catatan Kategori:</strong>
                    <ul class="mb-0 mt-2">
                        <li><strong>Inject - Unggah Dokumen:</strong> Aktivitas unggah_dokumen yang details-nya bukan "unggah_dokumen" (data inject)</li>
                        <li><strong>Inject - Mapping Dokumen:</strong> Aktivitas mapping_dokumen yang details-nya mengandung kata "inject"</li>
                        <li><strong>Unggah Dokumen:</strong> Aktivitas unggah_dokumen normal (details = "unggah_dokumen")</li>
                        <li><strong>Mapping Dokumen:</strong> Aktivitas mapping_dokumen manual (tanpa inject)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
