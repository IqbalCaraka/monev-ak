@extends('layouts.app')

@section('title', 'Hasil Perhitungan Skor Arsip')

@section('content')

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-title text-md-center text-xl-left mb-1">Total Data</p>
                        <h3 class="font-weight-bold mb-0">{{ number_format($stats['total']) }}</h3>
                    </div>
                    <i class="mdi mdi-database icon-lg text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-title text-md-center text-xl-left mb-1">PNS</p>
                        <h3 class="font-weight-bold mb-0">{{ number_format($stats['pns']) }}</h3>
                    </div>
                    <i class="mdi mdi-account-check icon-lg text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-title text-md-center text-xl-left mb-1">CPNS</p>
                        <h3 class="font-weight-bold mb-0">{{ number_format($stats['cpns']) }}</h3>
                    </div>
                    <i class="mdi mdi-account-clock icon-lg text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="card-title text-md-center text-xl-left mb-1">Lengkap</p>
                        <h3 class="font-weight-bold mb-0">
                            {{ number_format($stats['lengkap']) }}
                            <small class="text-muted">({{ $stats['persen_lengkap'] }}%)</small>
                        </h3>
                    </div>
                    <i class="mdi mdi-check-circle icon-lg text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Analisis Pengelompokan Berdasarkan Kondisi Arsip -->
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">🔍 Analisis Pengelompokan Berdasarkan Kondisi Arsip</h5>
                <p class="text-muted mb-3">Mengelompokkan pegawai berdasarkan kelengkapan dokumen arsip yang dimiliki</p>

                @php
                    // Inisialisasi array pengelompokan
                    $groups = [];

                    // Loop setiap hasil dan kelompokkan berdasarkan kondisi arsip
                    foreach($results as $result) {
                        $calc = $result['skor_calculated'];

                        // 1. Cek kelengkapan arsip utama
                        $arsipUtama = [
                            'drh' => $calc['detail']['drh']['skor'] >= 7.5,
                            'd2nip' => $calc['detail']['d2nip']['skor'] >= 7.5,
                            'cpns' => $calc['detail']['cpns']['skor'] >= 7.5,
                            'pns' => $calc['detail']['pns']['skor'] >= 7.5,
                            'pendidikan' => $calc['detail']['pendidikan']['skor'] >= 15,
                            'golongan' => $calc['detail']['golongan']['skor'] >= 15,
                            'jabatan' => $calc['detail']['jabatan']['skor'] >= 15,
                            'diklat' => $calc['detail']['diklat']['skor'] >= 15,
                        ];

                        // 2. Cek kepemilikan arsip kondisional
                        $arsipKondisional = [
                            'penghargaan' => $calc['kondisional']['penghargaan']['jumlah'] > 0,
                            'kinerja' => $calc['kondisional']['kinerja']['jumlah'] > 0,
                            'pindah_instansi' => $calc['kondisional']['pindah_instansi']['jumlah'] > 0,
                        ];

                        // 3. Hitung jumlah lengkap
                        $jumlahUtamaLengkap = array_sum($arsipUtama);
                        $jumlahKondisionalDimiliki = array_sum($arsipKondisional);

                        // 4. Identifikasi dokumen yang tidak dimiliki
                        $tidakPunya = [];
                        foreach($arsipUtama as $key => $value) {
                            if (!$value) {
                                $tidakPunya[] = $key;
                            }
                        }

                        // 5. Buat signature/kunci untuk grouping
                        $signature = sprintf(
                            'utama_%d_kondisional_%d_%s',
                            $jumlahUtamaLengkap,
                            $jumlahKondisionalDimiliki,
                            implode('_', $tidakPunya)
                        );

                        // 6. Simpan ke grup
                        if (!isset($groups[$signature])) {
                            $groups[$signature] = [
                                'jumlah_utama_lengkap' => $jumlahUtamaLengkap,
                                'jumlah_kondisional' => $jumlahKondisionalDimiliki,
                                'dokumen_tidak_punya' => $tidakPunya,
                                'arsip_utama_detail' => $arsipUtama,
                                'arsip_kondisional_detail' => $arsipKondisional,
                                'nip_list' => [],
                                'count' => 0,
                                'avg_skor_utama' => 0,
                                'avg_skor_final_sim1' => 0,
                                'avg_skor_final_sim2' => 0,
                                'avg_skor_csv' => 0,
                                'total_skor_utama' => 0,
                                'total_skor_sim1' => 0,
                                'total_skor_sim2' => 0,
                                'total_skor_csv' => 0,
                            ];
                        }

                        $groups[$signature]['nip_list'][] = [
                            'nip' => $result['nip'],
                            'nama' => $result['nama'],
                            'skor_utama' => $calc['skor_utama'],
                            'skor_sim1' => $calc['simulasi1']['skor_final'],
                            'skor_sim2' => $calc['simulasi2']['skor_final'],
                            'skor_csv' => $result['skor']['skor_arsip_2026'],
                        ];
                        $groups[$signature]['count']++;
                        $groups[$signature]['total_skor_utama'] += $calc['skor_utama'];
                        $groups[$signature]['total_skor_sim1'] += $calc['simulasi1']['skor_final'];
                        $groups[$signature]['total_skor_sim2'] += $calc['simulasi2']['skor_final'];
                        $groups[$signature]['total_skor_csv'] += $result['skor']['skor_arsip_2026'];
                    }

                    // 7. Hitung rata-rata dan sort berdasarkan count
                    foreach($groups as $key => &$group) {
                        $group['avg_skor_utama'] = $group['total_skor_utama'] / $group['count'];
                        $group['avg_skor_final_sim1'] = $group['total_skor_sim1'] / $group['count'];
                        $group['avg_skor_final_sim2'] = $group['total_skor_sim2'] / $group['count'];
                        $group['avg_skor_csv'] = $group['total_skor_csv'] / $group['count'];
                    }

                    // Sort berdasarkan jumlah terbanyak
                    uasort($groups, function($a, $b) {
                        return $b['count'] - $a['count'];
                    });

                    $totalPegawai = count($results);
                @endphp

                <div class="mb-3">
                    <div class="alert alert-info">
                        <strong>Total Kelompok Ditemukan: {{ count($groups) }}</strong> dari {{ $totalPegawai }} pegawai
                    </div>
                </div>

                <!-- Accordion untuk setiap kelompok -->
                <div class="accordion" id="groupAnalysisAccordion">
                    @foreach($groups as $index => $group)
                        @php
                            $groupId = 'group_' . $index;
                            $percentage = ($group['count'] / $totalPegawai) * 100;

                            // Generate deskripsi kelompok
                            $deskripsiArsipUtama = [];
                            foreach($group['arsip_utama_detail'] as $key => $val) {
                                if ($val) {
                                    $deskripsiArsipUtama[] = ucfirst($key);
                                }
                            }

                            $deskripsiKondisional = [];
                            foreach($group['arsip_kondisional_detail'] as $key => $val) {
                                if ($val) {
                                    $deskripsiKondisional[] = ucfirst(str_replace('_', ' ', $key));
                                }
                            }
                        @endphp

                        <div class="accordion-item mb-2 border rounded">
                            <h2 class="accordion-header" id="heading{{ $groupId }}">
                                <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $groupId }}" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $groupId }}">
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Kelompok #{{ $loop->iteration }}</strong>
                                                <span class="badge badge-primary ms-2">{{ $group['count'] }} pegawai ({{ number_format($percentage, 1) }}%)</span>
                                                @if($group['jumlah_utama_lengkap'] == 8 && $group['jumlah_kondisional'] > 0)
                                                    <span class="badge badge-success ms-1">Lengkap</span>
                                                @elseif($group['jumlah_utama_lengkap'] == 8 && $group['jumlah_kondisional'] == 0)
                                                    <span class="badge badge-warning ms-1">Utama Lengkap</span>
                                                @else
                                                    <span class="badge badge-secondary ms-1">Tidak Lengkap</span>
                                                @endif
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Arsip Utama: {{ $group['jumlah_utama_lengkap'] }}/8 lengkap
                                            @if(count($group['dokumen_tidak_punya']) > 0)
                                                | Tidak punya: {{ implode(', ', array_map('ucfirst', $group['dokumen_tidak_punya'])) }}
                                            @endif
                                            @if($group['jumlah_kondisional'] > 0)
                                                | Kondisional: {{ $group['jumlah_kondisional'] }}/3
                                            @endif
                                        </small>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse{{ $groupId }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" aria-labelledby="heading{{ $groupId }}" data-bs-parent="#groupAnalysisAccordion">
                                <div class="accordion-body">
                                    <!-- Detail Kelompok -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <h6 class="mb-2">📋 Status Arsip Utama (8 Jenis)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Jenis Dokumen</th>
                                                            <th class="text-center">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($group['arsip_utama_detail'] as $key => $status)
                                                            <tr>
                                                                <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                                <td class="text-center">
                                                                    @if($status)
                                                                        <i class="mdi mdi-check-circle text-success"></i> <span class="text-success">Lengkap</span>
                                                                    @else
                                                                        <i class="mdi mdi-close-circle text-danger"></i> <span class="text-danger">Tidak Lengkap</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <h6 class="mb-2">📦 Status Arsip Kondisional (3 Jenis)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Jenis Dokumen</th>
                                                            <th class="text-center">Kepemilikan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($group['arsip_kondisional_detail'] as $key => $status)
                                                            <tr>
                                                                <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                                                <td class="text-center">
                                                                    @if($status)
                                                                        <i class="mdi mdi-check-circle text-success"></i> <span class="text-success">Ada</span>
                                                                    @else
                                                                        <i class="mdi mdi-minus-circle text-secondary"></i> <span class="text-secondary">Tidak Ada</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            @if($group['jumlah_kondisional'] == 0)
                                                <div class="alert alert-warning mt-2 mb-0">
                                                    <small><i class="mdi mdi-information"></i> Tidak memiliki arsip kondisional → Mendapat bonus +10 poin</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Statistik Skor -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h6 class="mb-2">📊 Statistik Skor Kelompok</h6>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="card border-info">
                                                        <div class="card-body text-center p-2">
                                                            <small class="text-muted">Rata-rata Skor Utama</small>
                                                            <h5 class="mb-0 mt-1">{{ number_format($group['avg_skor_utama'], 2) }}</h5>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="card border-primary">
                                                        <div class="card-body text-center p-2">
                                                            <small class="text-muted">Rata-rata Final (Sim 1)</small>
                                                            <h5 class="mb-0 mt-1">{{ number_format($group['avg_skor_final_sim1'], 2) }}</h5>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="card border-success">
                                                        <div class="card-body text-center p-2">
                                                            <small class="text-muted">Rata-rata Final (Sim 2)</small>
                                                            <h5 class="mb-0 mt-1">{{ number_format($group['avg_skor_final_sim2'], 2) }}</h5>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="card border-secondary">
                                                        <div class="card-body text-center p-2">
                                                            <small class="text-muted">Rata-rata Skor CSV</small>
                                                            <h5 class="mb-0 mt-1">{{ number_format($group['avg_skor_csv'], 2) }}</h5>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Daftar NIP -->
                                    <div class="row">
                                        <div class="col-12">
                                            <h6 class="mb-2">👥 Daftar Pegawai ({{ $group['count'] }} orang)</h6>
                                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                                <table class="table table-sm table-hover table-bordered">
                                                    <thead class="table-light sticky-top">
                                                        <tr>
                                                            <th width="5%">No</th>
                                                            <th width="15%">NIP</th>
                                                            <th width="30%">Nama</th>
                                                            <th width="10%" class="text-center">Skor Utama</th>
                                                            <th width="10%" class="text-center">Skor Sim1</th>
                                                            <th width="10%" class="text-center">Skor Sim2</th>
                                                            <th width="10%" class="text-center">Skor CSV</th>
                                                            <th width="10%" class="text-center">Selisih Sim1</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($group['nip_list'] as $pegawai)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td><small>{{ $pegawai['nip'] }}</small></td>
                                                                <td><small><strong>{{ $pegawai['nama'] }}</strong></small></td>
                                                                <td class="text-center"><span class="badge badge-info">{{ number_format($pegawai['skor_utama'], 2) }}</span></td>
                                                                <td class="text-center"><span class="badge badge-primary">{{ number_format($pegawai['skor_sim1'], 2) }}</span></td>
                                                                <td class="text-center"><span class="badge badge-success">{{ number_format($pegawai['skor_sim2'], 2) }}</span></td>
                                                                <td class="text-center"><span class="badge badge-secondary">{{ number_format($pegawai['skor_csv'], 2) }}</span></td>
                                                                <td class="text-center">
                                                                    @php
                                                                        $selisih = $pegawai['skor_sim1'] - $pegawai['skor_csv'];
                                                                    @endphp
                                                                    <span class="badge badge-{{ $selisih > 0 ? 'success' : ($selisih < 0 ? 'danger' : 'secondary') }}">
                                                                        {{ $selisih > 0 ? '+' : '' }}{{ number_format($selisih, 2) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Interpretasi -->
                                    <div class="alert alert-light mt-3 mb-0">
                                        <strong>📌 Interpretasi Kelompok:</strong><br>
                                        <small>
                                            @if($group['jumlah_utama_lengkap'] == 8)
                                                ✅ Kelompok ini memiliki <strong>SEMUA arsip utama lengkap</strong> (8/8).
                                            @else
                                                ⚠️ Kelompok ini memiliki <strong>{{ $group['jumlah_utama_lengkap'] }}/8 arsip utama lengkap</strong>.
                                                @if(count($group['dokumen_tidak_punya']) > 0)
                                                    Dokumen yang tidak lengkap: <strong>{{ implode(', ', array_map('ucfirst', $group['dokumen_tidak_punya'])) }}</strong>.
                                                @endif
                                            @endif

                                            @if($group['jumlah_kondisional'] == 0)
                                                <br>💰 Tidak memiliki arsip kondisional sehingga mendapat <strong>bonus +10 poin</strong>.
                                            @elseif($group['jumlah_kondisional'] == 3)
                                                <br>✅ Memiliki <strong>SEMUA arsip kondisional</strong> (Penghargaan, Kinerja, Pindah Instansi).
                                            @else
                                                <br>📦 Memiliki <strong>{{ $group['jumlah_kondisional'] }}/3 jenis arsip kondisional</strong>:
                                                @if(count($deskripsiKondisional) > 0)
                                                    {{ implode(', ', $deskripsiKondisional) }}.
                                                @endif
                                            @endif

                                            <br>📈 Rata-rata selisih Sim1 vs CSV: <strong>{{ number_format($group['avg_skor_final_sim1'] - $group['avg_skor_csv'], 2) }}</strong> poin.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Summary Statistics -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-success">
                            <h6 class="mb-2"><i class="mdi mdi-chart-box"></i> Ringkasan Analisis Pengelompokan</h6>
                            @php
                                $groupLengkap = 0;
                                $groupUtamaLengkap = 0;
                                $groupTidakLengkap = 0;

                                foreach($groups as $g) {
                                    if ($g['jumlah_utama_lengkap'] == 8 && $g['jumlah_kondisional'] > 0) {
                                        $groupLengkap++;
                                    } elseif ($g['jumlah_utama_lengkap'] == 8) {
                                        $groupUtamaLengkap++;
                                    } else {
                                        $groupTidakLengkap++;
                                    }
                                }
                            @endphp
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Total Kelompok</small>
                                    <h4 class="mb-0">{{ count($groups) }}</h4>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Kelompok dengan Arsip Lengkap (Utama + Kondisional)</small>
                                    <h4 class="mb-0">{{ $groupLengkap }} <small class="text-muted">kelompok</small></h4>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Kelompok dengan Arsip Utama Lengkap Saja</small>
                                    <h4 class="mb-0">{{ $groupUtamaLengkap }} <small class="text-muted">kelompok</small></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Analisis Pola Selisih -->
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">📊 Analisis Pola Selisih (Skor Calculated vs Skor CSV)</h5>
                @php
                    $polaCounts = [
                        'Sama' => 0,
                        '+10' => 0,
                        '+15' => 0,
                        '+7.5' => 0,
                        'Positif' => 0,
                        'Negatif' => 0,
                    ];

                    foreach($results as $r) {
                        $selisih = $r['skor_calculated']['simulasi1']['skor_final'] - $r['skor']['skor_arsip_2026'];
                        if (abs($selisih) < 0.01) {
                            $polaCounts['Sama']++;
                        } elseif (abs($selisih - 10) < 0.01) {
                            $polaCounts['+10']++;
                        } elseif (abs($selisih - 15) < 0.01) {
                            $polaCounts['+15']++;
                        } elseif (abs($selisih - 7.5) < 0.01) {
                            $polaCounts['+7.5']++;
                        } elseif ($selisih > 0) {
                            $polaCounts['Positif']++;
                        } else {
                            $polaCounts['Negatif']++;
                        }
                    }

                    $totalData = count($results);
                @endphp
                <div class="row">
                    <div class="col-md-2">
                        <div class="text-center p-3 border rounded">
                            <h4 class="mb-1 text-secondary">{{ $polaCounts['Sama'] }}</h4>
                            <small class="text-muted">Sama (0)</small>
                            <br><small class="text-success">{{ $totalData > 0 ? number_format(($polaCounts['Sama'] / $totalData) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 border rounded">
                            <h4 class="mb-1 text-primary">{{ $polaCounts['+10'] }}</h4>
                            <small class="text-muted">+10 Poin</small>
                            <br><small class="text-success">{{ $totalData > 0 ? number_format(($polaCounts['+10'] / $totalData) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 border rounded">
                            <h4 class="mb-1 text-info">{{ $polaCounts['+15'] }}</h4>
                            <small class="text-muted">+15 Poin</small>
                            <br><small class="text-success">{{ $totalData > 0 ? number_format(($polaCounts['+15'] / $totalData) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 border rounded">
                            <h4 class="mb-1 text-warning">{{ $polaCounts['+7.5'] }}</h4>
                            <small class="text-muted">+7.5 Poin</small>
                            <br><small class="text-success">{{ $totalData > 0 ? number_format(($polaCounts['+7.5'] / $totalData) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 border rounded">
                            <h4 class="mb-1 text-success">{{ $polaCounts['Positif'] }}</h4>
                            <small class="text-muted">Positif Lain</small>
                            <br><small class="text-success">{{ $totalData > 0 ? number_format(($polaCounts['Positif'] / $totalData) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 border rounded">
                            <h4 class="mb-1 text-danger">{{ $polaCounts['Negatif'] }}</h4>
                            <small class="text-muted">Negatif</small>
                            <br><small class="text-danger">{{ $totalData > 0 ? number_format(($polaCounts['Negatif'] / $totalData) * 100, 1) : 0 }}%</small>
                        </div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="alert alert-info mb-0">
                    <small>
                        <strong>Interpretasi Pola:</strong><br>
                        • <strong>Sama (0)</strong>: Skor calculated sama persis dengan CSV<br>
                        • <strong>+10</strong>: Kemungkinan bonus arsip kondisional (tidak punya riwayat kondisional)<br>
                        • <strong>+15</strong>: Kemungkinan logika "Jumlah = 0" pada 1 riwayat (tidak punya = skor maksimal)<br>
                        • <strong>+7.5</strong>: Kemungkinan SK CPNS/SPMT CPNS atau dokumen arsip utama lainnya<br>
                        • <strong>Positif Lain</strong>: Selisih positif dengan nilai bervariasi<br>
                        • <strong>Negatif</strong>: Skor calculated lebih rendah dari CSV (perlu investigasi)
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rata-rata Statistics -->
<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left mb-1">Rata-rata Total Jumlah</p>
                <h4 class="font-weight-bold mb-0">{{ number_format($stats['avg_jumlah'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left mb-1">Rata-rata Total Dokumen</p>
                <h4 class="font-weight-bold mb-0">{{ number_format($stats['avg_dokumen'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card bg-success text-white">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left mb-1">Rata-rata Skor Calculated</p>
                <h4 class="font-weight-bold mb-0">{{ number_format($stats['avg_skor_calculated'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title text-md-center text-xl-left mb-1">Rata-rata Skor CSV</p>
                <h4 class="font-weight-bold mb-0">{{ number_format($stats['avg_skor_csv'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Main Table -->
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Hasil Perhitungan Skor Arsip</h4>
                        <p class="text-muted mb-0">File: <strong>{{ $fileName }}</strong> | Total: <strong>{{ number_format($stats['total']) }}</strong> data PNS</p>
                    </div>
                    <a href="{{ route('skor-arsip.index') }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-upload"></i> Upload File Lain
                    </a>
                </div>

                @if(isset($message))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-check-circle"></i> <strong>{{ $message }}</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Filter & Search -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Cari NIP atau Nama...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="P">PNS</option>
                            <option value="C">CPNS</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="filterKategori">
                            <option value="">Semua Kategori</option>
                            <option value="Lengkap">Lengkap</option>
                            <option value="Tidak Lengkap">Tidak Lengkap</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-secondary w-100" id="resetFilter">
                            <i class="mdi mdi-refresh"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover" id="resultTable">
                        <thead>
                            <tr>
                                <th rowspan="2">No</th>
                                <th rowspan="2">NIP</th>
                                <th rowspan="2">Nama</th>
                                <th rowspan="2" class="text-center">Status</th>
                                <th rowspan="2" class="text-end">Total Jumlah</th>
                                <th rowspan="2" class="text-end">Total Dokumen</th>
                                <th rowspan="2" class="text-center">Kepemilikan<br>Kondisional</th>
                                <th colspan="2" class="text-center">Skor Calculated</th>
                                <th rowspan="2" class="text-center">Skor CSV</th>
                                <th rowspan="2" class="text-center">Selisih<br>Sim1</th>
                                <th rowspan="2" class="text-center">Selisih<br>Sim2</th>
                                <th rowspan="2" class="text-center">Pola<br>Selisih</th>
                                <th rowspan="2" class="text-center">Action</th>
                            </tr>
                            <tr>
                                <th class="text-center">Sim 1</th>
                                <th class="text-center">Sim 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $index => $result)
                                @php
                                    $totalJumlah = array_sum($result['jumlah']);
                                    $totalDokumen = array_sum($result['dokumen']);
                                    $selisihSim1 = $result['skor_calculated']['simulasi1']['skor_final'] - $result['skor']['skor_arsip_2026'];
                                    $selisihSim2 = $result['skor_calculated']['simulasi2']['skor_final'] - $result['skor']['skor_arsip_2026'];

                                    // Analisis pola selisih
                                    $polaSim1 = 'Sama';
                                    if (abs($selisihSim1) < 0.01) {
                                        $polaSim1 = 'Sama';
                                    } elseif (abs($selisihSim1 - 10) < 0.01) {
                                        $polaSim1 = '+10';
                                    } elseif (abs($selisihSim1 - 15) < 0.01) {
                                        $polaSim1 = '+15';
                                    } elseif (abs($selisihSim1 - 7.5) < 0.01) {
                                        $polaSim1 = '+7.5';
                                    } elseif ($selisihSim1 > 0) {
                                        $polaSim1 = 'Positif';
                                    } else {
                                        $polaSim1 = 'Negatif';
                                    }
                                @endphp
                                <tr class="data-row"
                                    data-nip="{{ $result['nip'] }}"
                                    data-nama="{{ strtolower($result['nama']) }}"
                                    data-status="{{ $result['status_cpns_pns'] }}"
                                    data-kategori="{{ $result['skor_calculated']['simulasi1']['kategori'] }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $result['nip'] }}</td>
                                    <td><strong>{{ $result['nama'] }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $result['status_cpns_pns'] == 'P' ? 'success' : 'info' }}">
                                            {{ $result['status_cpns_pns'] == 'P' ? 'PNS' : 'CPNS' }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ number_format($totalJumlah) }}</td>
                                    <td class="text-end">{{ number_format($totalDokumen) }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $result['skor_calculated']['ada_arsip_kondisional'] ? 'success' : 'secondary' }}">
                                            {{ $result['skor_calculated']['ada_arsip_kondisional'] ? 'Ya' : 'Tidak' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $result['skor_calculated']['simulasi1']['skor_final'] >= 100 ? 'success' : ($result['skor_calculated']['simulasi1']['skor_final'] >= 90 ? 'warning' : 'danger') }}">
                                            {{ number_format($result['skor_calculated']['simulasi1']['skor_final'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $result['skor_calculated']['simulasi2']['skor_final'] >= 100 ? 'success' : ($result['skor_calculated']['simulasi2']['skor_final'] >= 90 ? 'warning' : 'danger') }}">
                                            {{ number_format($result['skor_calculated']['simulasi2']['skor_final'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">
                                            {{ number_format($result['skor']['skor_arsip_2026'], 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $selisihSim1 > 0 ? 'success' : ($selisihSim1 < 0 ? 'danger' : 'secondary') }}">
                                            {{ $selisihSim1 > 0 ? '+' : '' }}{{ number_format($selisihSim1, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $selisihSim2 > 0 ? 'success' : ($selisihSim2 < 0 ? 'danger' : 'secondary') }}">
                                            {{ $selisihSim2 > 0 ? '+' : '' }}{{ number_format($selisihSim2, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{
                                            $polaSim1 == 'Sama' ? 'secondary' :
                                            ($polaSim1 == '+10' ? 'primary' :
                                            ($polaSim1 == '+15' ? 'info' :
                                            ($polaSim1 == '+7.5' ? 'warning' :
                                            ($polaSim1 == 'Positif' ? 'success' : 'danger'))))
                                        }}">
                                            {{ $polaSim1 }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal"
                                                onclick="showDetail({{ $index }})">
                                            <i class="ti-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="noResults" class="text-center py-4" style="display: none;">
                    <i class="mdi mdi-magnify icon-lg text-muted"></i>
                    <p class="text-muted mb-0 mt-2">Tidak ada data yang sesuai dengan filter</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel">Detail Data Pegawai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
// Store results data for detail view
const resultsData = @json($results);

// Show detail in modal
function showDetail(index) {
    const data = resultsData[index];
    const totalJumlah = Object.values(data.jumlah).reduce((a, b) => a + b, 0);
    const totalDokumen = Object.values(data.dokumen).reduce((a, b) => a + b, 0);

    const content = `
        <div class="mb-4">
            <h6 class="mb-3">Data Utama</h6>
            <table class="table table-sm table-borderless">
                <tr><td width="150"><strong>NIP</strong></td><td>: ${data.nip}</td></tr>
                <tr><td><strong>Nama</strong></td><td>: ${data.nama}</td></tr>
                <tr><td><strong>Status</strong></td><td>: <span class="badge badge-${data.status_cpns_pns == 'P' ? 'success' : 'info'}">${data.status_cpns_pns == 'P' ? 'PNS' : 'CPNS'}</span></td></tr>
            </table>
        </div>

        <div class="mb-4">
            <h6 class="mb-3">Data Jumlah (Kolom W-AC)</h6>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><td>Jumlah Golongan</td><td class="text-end"><strong>${data.jumlah.jumlah_golongan.toLocaleString()}</strong></td></tr>
                        <tr><td>Jumlah Jabatan</td><td class="text-end"><strong>${data.jumlah.jumlah_jabatan.toLocaleString()}</strong></td></tr>
                        <tr><td>Jumlah Pendidikan</td><td class="text-end"><strong>${data.jumlah.jumlah_pendidikan.toLocaleString()}</strong></td></tr>
                        <tr><td>Jumlah Diklat</td><td class="text-end"><strong>${data.jumlah.jumlah_diklat.toLocaleString()}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><td>Jumlah Penghargaan</td><td class="text-end"><strong>${data.jumlah.jumlah_penghargaan.toLocaleString()}</strong></td></tr>
                        <tr><td>Jumlah Kinerja</td><td class="text-end"><strong>${data.jumlah.jumlah_kinerja.toLocaleString()}</strong></td></tr>
                        <tr><td>Jumlah Pindah Instansi</td><td class="text-end"><strong>${data.jumlah.jumlah_pindah_instansi.toLocaleString()}</strong></td></tr>
                        <tr class="table-info"><td><strong>TOTAL</strong></td><td class="text-end"><strong>${totalJumlah.toLocaleString()}</strong></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="mb-3">Data Dokumen (Kolom AD-AO)</h6>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><td>Dokumen D2NP</td><td class="text-end"><strong>${data.dokumen.dok_d2np.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen DRH</td><td class="text-end"><strong>${data.dokumen.dok_drh.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen SPMT CPNS</td><td class="text-end"><strong>${data.dokumen.dok_spmt_cpns.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen CPNS</td><td class="text-end"><strong>${data.dokumen.dok_cpns.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen PNS</td><td class="text-end"><strong>${data.dokumen.dok_pns.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen Golongan</td><td class="text-end"><strong>${data.dokumen.dok_golongan.toLocaleString()}</strong></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><td>Dokumen Jabatan</td><td class="text-end"><strong>${data.dokumen.dok_jabatan.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen Pendidikan</td><td class="text-end"><strong>${data.dokumen.dok_pendidikan.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen Diklat</td><td class="text-end"><strong>${data.dokumen.dok_diklat.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen Penghargaan</td><td class="text-end"><strong>${data.dokumen.dok_penghargaan.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen Kinerja</td><td class="text-end"><strong>${data.dokumen.dok_kinerja.toLocaleString()}</strong></td></tr>
                        <tr><td>Dokumen Pindah Instansi</td><td class="text-end"><strong>${data.dokumen.dok_pindah_instansi.toLocaleString()}</strong></td></tr>
                    </table>
                </div>
                <div class="col-12">
                    <div class="alert alert-info text-center mb-0">
                        <strong>TOTAL DOKUMEN: ${totalDokumen.toLocaleString()}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h6 class="mb-3">Perhitungan Skor (Berdasarkan Ketentuan)</h6>
            <div class="alert alert-info mb-3">
                <small><strong>Ketentuan:</strong><br>
                • DRH (7.5) + D2NIP (7.5) + SK CPNS (7.5) + SK PNS (7.5) + Pendidikan (15) + Golongan (15) + Jabatan (15) + Diklat (15) = <strong>Max 90</strong><br>
                • Jika <strong>Jumlah = 0</strong> (tidak punya riwayat) → Skor <strong>Maksimal</strong><br>
                • Jika <strong>Jumlah > 0</strong> (punya riwayat) → Skor = <strong>(Dokumen / Jumlah) × Bobot</strong><br>
                • Jika tidak ada Arsip Kondisional dan skor utama = 90 → Skor otomatis <strong>100</strong></small>
            </div>

            <h6 class="mb-2">Arsip Utama:</h6>
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Jenis Arsip</th>
                        <th class="text-center">Dokumen</th>
                        <th class="text-center">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>DRH (fixed)</td>
                        <td class="text-center">${data.skor_calculated.detail.drh.dokumen}</td>
                        <td class="text-center"><span class="badge badge-success">${data.skor_calculated.detail.drh.skor.toFixed(2)}</span></td>
                    </tr>
                    <tr>
                        <td>D2NIP</td>
                        <td class="text-center">${data.skor_calculated.detail.d2nip.dokumen}</td>
                        <td class="text-center"><span class="badge badge-${data.skor_calculated.detail.d2nip.skor > 0 ? 'success' : 'secondary'}">${data.skor_calculated.detail.d2nip.skor.toFixed(2)}</span></td>
                    </tr>
                    <tr>
                        <td>SK CPNS/SPMT CPNS<br><small class="text-muted">(CPNS: ${data.skor_calculated.detail.cpns.dok_cpns} | SPMT: ${data.skor_calculated.detail.cpns.dok_spmt_cpns})</small></td>
                        <td class="text-center">${data.skor_calculated.detail.cpns.dokumen}</td>
                        <td class="text-center"><span class="badge badge-${data.skor_calculated.detail.cpns.skor > 0 ? 'success' : 'secondary'}">${data.skor_calculated.detail.cpns.skor.toFixed(2)}</span></td>
                    </tr>
                    <tr>
                        <td>SK PNS</td>
                        <td class="text-center">${data.skor_calculated.detail.pns.dokumen}</td>
                        <td class="text-center"><span class="badge badge-${data.skor_calculated.detail.pns.skor > 0 ? 'success' : 'secondary'}">${data.skor_calculated.detail.pns.skor.toFixed(2)}</span></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="mb-2 mt-3">Riwayat (dengan Jumlah):</h6>
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Jenis Riwayat</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Dokumen</th>
                        <th class="text-center">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Riwayat Pendidikan</td>
                        <td class="text-center">${data.skor_calculated.detail.pendidikan.jumlah}</td>
                        <td class="text-center">${data.skor_calculated.detail.pendidikan.dokumen}</td>
                        <td class="text-center"><span class="badge badge-${data.skor_calculated.detail.pendidikan.skor >= 15 ? 'success' : (data.skor_calculated.detail.pendidikan.skor > 0 ? 'warning' : 'secondary')}">${data.skor_calculated.detail.pendidikan.skor.toFixed(2)}</span></td>
                    </tr>
                    <tr>
                        <td>Riwayat Kenaikan Pangkat (Golongan)</td>
                        <td class="text-center">${data.skor_calculated.detail.golongan.jumlah}</td>
                        <td class="text-center">${data.skor_calculated.detail.golongan.dokumen}</td>
                        <td class="text-center"><span class="badge badge-${data.skor_calculated.detail.golongan.skor >= 15 ? 'success' : (data.skor_calculated.detail.golongan.skor > 0 ? 'warning' : 'secondary')}">${data.skor_calculated.detail.golongan.skor.toFixed(2)}</span></td>
                    </tr>
                    <tr>
                        <td>Riwayat Jabatan</td>
                        <td class="text-center">${data.skor_calculated.detail.jabatan.jumlah}</td>
                        <td class="text-center">${data.skor_calculated.detail.jabatan.dokumen}</td>
                        <td class="text-center"><span class="badge badge-${data.skor_calculated.detail.jabatan.skor >= 15 ? 'success' : (data.skor_calculated.detail.jabatan.skor > 0 ? 'warning' : 'secondary')}">${data.skor_calculated.detail.jabatan.skor.toFixed(2)}</span></td>
                    </tr>
                    <tr>
                        <td>Riwayat Diklat</td>
                        <td class="text-center">${data.skor_calculated.detail.diklat.jumlah}</td>
                        <td class="text-center">${data.skor_calculated.detail.diklat.dokumen}</td>
                        <td class="text-center"><span class="badge badge-${data.skor_calculated.detail.diklat.skor >= 15 ? 'success' : (data.skor_calculated.detail.diklat.skor > 0 ? 'warning' : 'secondary')}">${data.skor_calculated.detail.diklat.skor.toFixed(2)}</span></td>
                    </tr>
                    <tr class="table-info">
                        <td colspan="3"><strong>TOTAL SKOR UTAMA</strong></td>
                        <td class="text-center"><strong>${data.skor_calculated.skor_utama.toFixed(2)}</strong></td>
                    </tr>
                </tbody>
            </table>

            <h6 class="mb-2 mt-4">Arsip Kondisional (Max 10):</h6>
            ${data.skor_calculated.ada_arsip_kondisional ? `
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Jenis Arsip</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-center">Dokumen</th>
                            <th class="text-center">Kepemilikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Penghargaan</td>
                            <td class="text-center">${data.skor_calculated.kondisional.penghargaan.jumlah}</td>
                            <td class="text-center">${data.skor_calculated.kondisional.penghargaan.dokumen}</td>
                            <td class="text-center">
                                <span class="badge badge-${data.skor_calculated.kondisional.penghargaan.jumlah > 0 ? 'success' : 'secondary'}">
                                    ${data.skor_calculated.kondisional.penghargaan.jumlah > 0 ? 'Ya' : 'Tidak'}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Kinerja</td>
                            <td class="text-center">${data.skor_calculated.kondisional.kinerja.jumlah}</td>
                            <td class="text-center">${data.skor_calculated.kondisional.kinerja.dokumen}</td>
                            <td class="text-center">
                                <span class="badge badge-${data.skor_calculated.kondisional.kinerja.jumlah > 0 ? 'success' : 'secondary'}">
                                    ${data.skor_calculated.kondisional.kinerja.jumlah > 0 ? 'Ya' : 'Tidak'}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Pindah Instansi</td>
                            <td class="text-center">${data.skor_calculated.kondisional.pindah_instansi.jumlah}</td>
                            <td class="text-center">${data.skor_calculated.kondisional.pindah_instansi.dokumen}</td>
                            <td class="text-center">
                                <span class="badge badge-${data.skor_calculated.kondisional.pindah_instansi.jumlah > 0 ? 'success' : 'secondary'}">
                                    ${data.skor_calculated.kondisional.pindah_instansi.jumlah > 0 ? 'Ya' : 'Tidak'}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <strong>SIMULASI 1: Proporsional Total</strong>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Rumus:</strong></p>
                                <code>(Total Dokumen / Total Jumlah) × 10</code>
                                <hr>
                                <p class="mb-2"><strong>Perhitungan:</strong></p>
                                <code>(${data.skor_calculated.kondisional.total_dokumen} / ${data.skor_calculated.kondisional.total_jumlah}) × 10 = ${data.skor_calculated.simulasi1.skor_kondisional.toFixed(2)}</code>
                                <hr>
                                <div class="text-center">
                                    <h5 class="mb-0">Skor: <span class="badge badge-warning">${data.skor_calculated.simulasi1.skor_kondisional.toFixed(2)}</span></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <strong>SIMULASI 2: Per Jenis Kondisional</strong>
                            </div>
                            <div class="card-body">
                                <p class="mb-2"><strong>Rumus:</strong></p>
                                <code>Σ [(Dok / Jml) × (10 / Jml Jenis)]</code>
                                <hr>
                                <p class="mb-2"><strong>Bobot per Jenis:</strong> <code>10 / ${data.skor_calculated.kondisional.jenis_dimiliki} = ${data.skor_calculated.kondisional.bobot_per_jenis.toFixed(2)}</code></p>
                                <p class="mb-2"><strong>Perhitungan:</strong></p>
                                <small>
                                ${data.skor_calculated.kondisional.penghargaan.jumlah > 0 ?
                                    `Penghargaan: (${data.skor_calculated.kondisional.penghargaan.dokumen}/${data.skor_calculated.kondisional.penghargaan.jumlah}) × ${data.skor_calculated.kondisional.bobot_per_jenis.toFixed(2)}<br>` :
                                    ''}
                                ${data.skor_calculated.kondisional.kinerja.jumlah > 0 ?
                                    `Kinerja: (${data.skor_calculated.kondisional.kinerja.dokumen}/${data.skor_calculated.kondisional.kinerja.jumlah}) × ${data.skor_calculated.kondisional.bobot_per_jenis.toFixed(2)}<br>` :
                                    ''}
                                ${data.skor_calculated.kondisional.pindah_instansi.jumlah > 0 ?
                                    `Pindah: (${data.skor_calculated.kondisional.pindah_instansi.dokumen}/${data.skor_calculated.kondisional.pindah_instansi.jumlah}) × ${data.skor_calculated.kondisional.bobot_per_jenis.toFixed(2)}` :
                                    ''}
                                </small>
                                <hr>
                                <div class="text-center">
                                    <h5 class="mb-0">Skor: <span class="badge badge-warning">${data.skor_calculated.simulasi2.skor_kondisional.toFixed(2)}</span></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            ` : `
                <div class="alert alert-light text-center">
                    <small class="text-muted">Tidak ada Arsip Kondisional</small>
                </div>
            `}

            <hr class="my-4">
            <h6 class="mb-3">Ringkasan Skor:</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <strong>SIMULASI 1</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Skor Utama</td>
                                    <td class="text-end"><strong>${data.skor_calculated.skor_utama.toFixed(2)}</strong></td>
                                </tr>
                                <tr>
                                    <td>Skor Kondisional</td>
                                    <td class="text-end"><strong>${data.skor_calculated.simulasi1.skor_kondisional.toFixed(2)}</strong></td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>SKOR FINAL</strong></td>
                                    <td class="text-end"><strong>${data.skor_calculated.simulasi1.skor_final.toFixed(2)}</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <span class="badge badge-${data.skor_calculated.simulasi1.kategori == 'Lengkap' ? 'success' : 'secondary'}">
                                            ${data.skor_calculated.simulasi1.kategori.toUpperCase()}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <strong>SIMULASI 2</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td>Skor Utama</td>
                                    <td class="text-end"><strong>${data.skor_calculated.skor_utama.toFixed(2)}</strong></td>
                                </tr>
                                <tr>
                                    <td>Skor Kondisional</td>
                                    <td class="text-end"><strong>${data.skor_calculated.simulasi2.skor_kondisional.toFixed(2)}</strong></td>
                                </tr>
                                <tr class="border-top">
                                    <td><strong>SKOR FINAL</strong></td>
                                    <td class="text-end"><strong>${data.skor_calculated.simulasi2.skor_final.toFixed(2)}</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <span class="badge badge-${data.skor_calculated.simulasi2.kategori == 'Lengkap' ? 'success' : 'secondary'}">
                                            ${data.skor_calculated.simulasi2.kategori.toUpperCase()}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h6 class="mb-3">Data Skor dari CSV (Kolom BH-BL)</h6>
            <table class="table table-sm">
                <tr><td>Skor Arsip Digital 30 Okt</td><td class="text-end"><span class="badge badge-info">${data.skor.skor_arsip_digital_30okt.toFixed(2)}</span></td></tr>
                <tr><td>Skor Arsip Digital</td><td class="text-end"><span class="badge badge-info">${data.skor.skor_arsip_digital.toFixed(2)}</span></td></tr>
                <tr><td>Is Terisi</td><td class="text-end"><span class="badge badge-${data.skor.is_terisi == 1 ? 'success' : 'secondary'}">${data.skor.is_terisi == 1 ? 'Ya' : 'Tidak'}</span></td></tr>
                <tr><td>Skor Arsip 2026</td><td class="text-end"><span class="badge badge-secondary">${data.skor.skor_arsip_2026.toFixed(2)}</span></td></tr>
                <tr><td>Kategori Kelengkapan 2026</td><td class="text-end"><span class="badge badge-${data.skor.kategori_kelengkapan_2026 == 'Lengkap' ? 'success' : 'secondary'}">${data.skor.kategori_kelengkapan_2026 || '-'}</span></td></tr>
            </table>
        </div>
    `;

    document.getElementById('modalContent').innerHTML = content;
}

// Filter and search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const filterKategori = document.getElementById('filterKategori');
    const resetBtn = document.getElementById('resetFilter');
    const rows = document.querySelectorAll('.data-row');
    const noResults = document.getElementById('noResults');

    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusFilter = filterStatus.value;
        const kategoriFilter = filterKategori.value;
        let visibleCount = 0;

        rows.forEach((row, index) => {
            const nip = row.dataset.nip;
            const nama = row.dataset.nama;
            const status = row.dataset.status;
            const kategori = row.dataset.kategori;

            const matchSearch = searchTerm === '' || nip.includes(searchTerm) || nama.includes(searchTerm);
            const matchStatus = statusFilter === '' || status === statusFilter;
            const matchKategori = kategoriFilter === '' || kategori === kategoriFilter;

            if (matchSearch && matchStatus && matchKategori) {
                row.style.display = '';
                row.querySelector('td:first-child').textContent = ++visibleCount;
            } else {
                row.style.display = 'none';
            }
        });

        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('keyup', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterKategori.addEventListener('change', applyFilters);

    resetBtn.addEventListener('click', function() {
        searchInput.value = '';
        filterStatus.value = '';
        filterKategori.value = '';
        applyFilters();
    });
});
</script>

@endsection
