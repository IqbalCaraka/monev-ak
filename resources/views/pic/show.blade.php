@extends('layouts.app')

@section('title', 'Detail PIC DMS')

@section('content')

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Detail PIC DMS</h4>
                        <p class="text-muted mb-0">Person In Charge Document Management System - Statistik Performa Tim</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('pic.edit', $pic->id) }}" class="btn btn-warning">
                            <i class="mdi mdi-pencil"></i> Edit
                        </a>
                        <a href="{{ route('pic.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <!-- Info PIC -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Ketua PIC DMS:</th>
                                <td>
                                    @if($pic->ketua)
                                        <strong>{{ $pic->ketua->nama }}</strong> <span class="text-muted">(NIP: {{ $pic->ketua->nip }})</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($pic->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak Aktif</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Dibuat:</th>
                                <td>{{ $pic->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Ringkasan</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Anggota Tim:</span>
                                    <strong>{{ $pic->anggota_count }} orang</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total Instansi:</span>
                                    <strong>{{ $pic->instansi_count }} instansi</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Anggota Tim -->
                <div class="mb-4">
                    <h5 class="mb-3">Anggota Tim ({{ $pic->anggota_count }})</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>NIP</th>
                                    <th>Nama</th>
                                    <th>Role</th>
                                    <th>Bergabung</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pic->anggota as $index => $anggota)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $anggota->nip }}</td>
                                        <td>{{ $anggota->nama }}</td>
                                        <td>
                                            @if($anggota->pivot->role == 'ketua')
                                                <span class="badge badge-primary">Ketua</span>
                                            @else
                                                <span class="badge badge-info">{{ ucfirst($anggota->pivot->role) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($anggota->pivot->assigned_at)->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">Belum ada anggota tim</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Instansi yang Dipegang -->
                <div class="mb-4">
                    <h5 class="mb-3">Instansi yang Dipegang ({{ $pic->instansi_count }})</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama Instansi</th>
                                    <th>Ditugaskan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pic->instansi as $index => $inst)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $inst->nama }}</td>
                                        <td>{{ \Carbon\Carbon::parse($inst->pivot->assigned_at)->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Belum ada instansi yang ditugaskan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Statistik Performa Tim -->
                <div class="mb-4">
                    <h5 class="mb-3">Statistik Performa Tim</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Total Aktivitas</h6>
                                    <h2 class="mb-0">{{ number_format($stats['total_aktivitas']) }}</h2>
                                    <small>Log aktivitas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Total Mapping</h6>
                                    <h2 class="mb-0">{{ number_format($stats['total_mapping']) }}</h2>
                                    <small>Dokumen dimapping</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Total Inject</h6>
                                    <h2 class="mb-0">{{ number_format($stats['total_inject']) }}</h2>
                                    <small>Dokumen diinject</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Unique PNS</h6>
                                    <h2 class="mb-0">{{ number_format($stats['unique_pns']) }}</h2>
                                    <small>PNS unik diproses</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performa Anggota -->
                <div>
                    <h5 class="mb-3">Performa Individual Anggota</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama</th>
                                    <th class="text-end">Total Aktivitas</th>
                                    <th class="text-end">Mapping</th>
                                    <th class="text-end">Inject</th>
                                    <th class="text-end">Unique PNS</th>
                                    <th class="text-center">Kontribusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalTeamActivities = collect($performaAnggota)->sum('total_aktivitas') ?: 1;
                                @endphp
                                @forelse($performaAnggota as $index => $performa)
                                    @php
                                        $kontribusiPersen = ($performa->total_aktivitas / $totalTeamActivities) * 100;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $performa->nama }}</strong>
                                            <br><small class="text-muted">{{ $performa->nip }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($performa->total_aktivitas) }}</td>
                                        <td class="text-end">{{ number_format($performa->total_mapping) }}</td>
                                        <td class="text-end">{{ number_format($performa->total_inject) }}</td>
                                        <td class="text-end">{{ number_format($performa->unique_pns) }}</td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar"
                                                     role="progressbar"
                                                     style="width: {{ $kontribusiPersen }}%;"
                                                     aria-valuenow="{{ $kontribusiPersen }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    {{ number_format($kontribusiPersen, 1) }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">Belum ada data performa</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
