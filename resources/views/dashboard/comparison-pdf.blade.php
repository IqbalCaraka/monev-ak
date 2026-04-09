<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Analisis Perbandingan Periode - Monev DMS</title>
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 14px;
            margin: 0 0 3px 0;
            color: #2c3e50;
        }

        .header h2 {
            font-size: 10px;
            margin: 0;
            color: #7f8c8d;
            font-weight: normal;
        }

        .header p {
            font-size: 8px;
            margin: 3px 0;
            color: #7f8c8d;
        }

        .info-box {
            background-color: #ecf0f1;
            padding: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #3498db;
        }

        .info-box div {
            margin-bottom: 3px;
            display: inline-block;
            width: 48%;
        }

        .info-box .label {
            font-weight: bold;
            color: #2c3e50;
            margin-right: 5px;
        }

        .summary-stats {
            margin-bottom: 15px;
        }

        .summary-stats table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-stats td {
            padding: 8px;
            text-align: center;
            color: white;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th {
            background-color: #3498db;
            color: white;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 8px;
            border: 1px solid #2980b9;
        }

        table td {
            padding: 5px;
            border: 1px solid #bdc3c7;
            font-size: 7px;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-center {
            text-align: center;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
            display: inline-block;
            color: white;
        }

        .status-naik {
            background-color: #27ae60;
        }

        .status-turun {
            background-color: #e74c3c;
        }

        .status-stagnan {
            background-color: #f39c12;
        }

        /* Prevent page break inside table header */
        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        /* New page for detail table */
        .page-break {
            page-break-before: always;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #2c3e50;
            margin: 15px 0 10px 0;
            padding: 8px;
            background-color: #ecf0f1;
            border-left: 5px solid #3498db;
        }

        /* Keep header rows together */
        table thead tr {
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        table tbody tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>BADAN KEPEGAWAIAN NEGARA</h1>
        <h2>Direktorat Arsip Kepegawaian ASN</h2>
        <p style="margin-top: 8px; font-size: 12px; font-weight: bold;">ANALISIS PERBANDINGAN PERIODE - MONEV DMS</p>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <div>
            <span class="label">Periode Awal:</span>
            <span>{{ \Carbon\Carbon::parse($previousDate)->format('d F Y') }}</span>
        </div>
        <div style="text-align: right;">
            <span class="label">Periode Akhir:</span>
            <span>{{ \Carbon\Carbon::parse($currentDate)->format('d F Y') }}</span>
        </div>
        <div>
            <span class="label">Total Instansi:</span>
            <span><strong>{{ count($changes) }} Instansi</strong></span>
        </div>
        <div style="text-align: right;">
            <span class="label">Waktu Cetak:</span>
            <span>{{ request('printed_at') ?? \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>

    <!-- Skor Nasional & Rata-rata Instansi -->
    @if($skorNasionalPrevious && $skorNasionalCurrent)
    <div style="background-color: #ecf0f1; padding: 8px; margin-bottom: 10px; border-left: 4px solid #27ae60;">
        <strong style="font-size: 9px; color: #2c3e50;">SKOR RATA-RATA ARSIP DMS NASIONAL</strong>
        <table cellpadding="5" style="margin-top: 5px; width: 100%; margin-bottom: 8px;">
            <tr>
                <td style="width: 30%; font-size: 7px;"><strong>Sebelum:</strong> {{ number_format($skorNasionalPrevious->skor_rata2_nasional, 2) }}</td>
                <td style="width: 30%; font-size: 7px;"><strong>Sekarang:</strong> {{ number_format($skorNasionalCurrent->skor_rata2_nasional, 2) }}</td>
                <td style="width: 40%; font-size: 7px;"><strong>Perubahan:</strong> {{ number_format($skorNasionalCurrent->skor_rata2_nasional - $skorNasionalPrevious->skor_rata2_nasional, 2) }}</td>
            </tr>
        </table>

        <!-- Distribusi ASN Detail -->
        <strong style="font-size: 8px; color: #2c3e50; display: block; margin-top: 8px; margin-bottom: 4px;">Distribusi ASN Berdasarkan Status Kelengkapan:</strong>
        <table style="width: 100%; border-collapse: collapse; font-size: 7px;">
            <thead>
                <tr style="background-color: #bdc3c7;">
                    <th style="padding: 4px; border: 1px solid #95a5a6; text-align: left;">Status</th>
                    <th style="padding: 4px; border: 1px solid #95a5a6; text-align: center;">Sebelum</th>
                    <th style="padding: 4px; border: 1px solid #95a5a6; text-align: center;">Sekarang</th>
                    <th style="padding: 4px; border: 1px solid #95a5a6; text-align: center;">Perubahan</th>
                    <th style="padding: 4px; border: 1px solid #95a5a6; text-align: center;">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Kurang Lengkap
                    $diffKurang = ($skorNasionalCurrent->kurang_lengkap ?? 0) - ($skorNasionalPrevious->kurang_lengkap ?? 0);
                    $pctKurang = ($skorNasionalPrevious->kurang_lengkap ?? 0) > 0 ? (($diffKurang / ($skorNasionalPrevious->kurang_lengkap ?? 1)) * 100) : 0;
                    $colorKurang = $diffKurang > 0 ? '#e74c3c' : ($diffKurang < 0 ? '#27ae60' : '#333');

                    // Cukup Lengkap
                    $diffCukup = ($skorNasionalCurrent->cukup_lengkap ?? 0) - ($skorNasionalPrevious->cukup_lengkap ?? 0);
                    $pctCukup = ($skorNasionalPrevious->cukup_lengkap ?? 0) > 0 ? (($diffCukup / ($skorNasionalPrevious->cukup_lengkap ?? 1)) * 100) : 0;
                    $colorCukup = $diffCukup > 0 ? '#27ae60' : ($diffCukup < 0 ? '#e74c3c' : '#333');

                    // Lengkap
                    $diffLengkap = ($skorNasionalCurrent->lengkap ?? 0) - ($skorNasionalPrevious->lengkap ?? 0);
                    $pctLengkap = ($skorNasionalPrevious->lengkap ?? 0) > 0 ? (($diffLengkap / ($skorNasionalPrevious->lengkap ?? 1)) * 100) : 0;
                    $colorLengkap = $diffLengkap > 0 ? '#27ae60' : ($diffLengkap < 0 ? '#e74c3c' : '#333');

                    // Sangat Lengkap
                    $diffSangat = ($skorNasionalCurrent->sangat_lengkap ?? 0) - ($skorNasionalPrevious->sangat_lengkap ?? 0);
                    $pctSangat = ($skorNasionalPrevious->sangat_lengkap ?? 0) > 0 ? (($diffSangat / ($skorNasionalPrevious->sangat_lengkap ?? 1)) * 100) : 0;
                    $colorSangat = $diffSangat > 0 ? '#27ae60' : ($diffSangat < 0 ? '#e74c3c' : '#333');
                @endphp

                <tr>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; background-color: #fff;">Kurang Lengkap</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff;">{{ number_format($skorNasionalPrevious->kurang_lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff;">{{ number_format($skorNasionalCurrent->kurang_lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff; color: {{ $colorKurang }};">
                        {{ $diffKurang >= 0 ? '+' : '' }}{{ number_format($diffKurang) }}
                    </td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff; color: {{ $colorKurang }}; font-weight: bold;">
                        {{ $pctKurang >= 0 ? '+' : '' }}{{ number_format($pctKurang, 2) }}%
                    </td>
                </tr>
                <tr>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; background-color: #f8f9fa;">Cukup Lengkap</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa;">{{ number_format($skorNasionalPrevious->cukup_lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa;">{{ number_format($skorNasionalCurrent->cukup_lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa; color: {{ $colorCukup }};">
                        {{ $diffCukup >= 0 ? '+' : '' }}{{ number_format($diffCukup) }}
                    </td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa; color: {{ $colorCukup }}; font-weight: bold;">
                        {{ $pctCukup >= 0 ? '+' : '' }}{{ number_format($pctCukup, 2) }}%
                    </td>
                </tr>
                <tr>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; background-color: #fff;">Lengkap</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff;">{{ number_format($skorNasionalPrevious->lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff;">{{ number_format($skorNasionalCurrent->lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff; color: {{ $colorLengkap }};">
                        {{ $diffLengkap >= 0 ? '+' : '' }}{{ number_format($diffLengkap) }}
                    </td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #fff; color: {{ $colorLengkap }}; font-weight: bold;">
                        {{ $pctLengkap >= 0 ? '+' : '' }}{{ number_format($pctLengkap, 2) }}%
                    </td>
                </tr>
                <tr>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; background-color: #f8f9fa;">Sangat Lengkap</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa;">{{ number_format($skorNasionalPrevious->sangat_lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa;">{{ number_format($skorNasionalCurrent->sangat_lengkap ?? 0) }}</td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa; color: {{ $colorSangat }};">
                        {{ $diffSangat >= 0 ? '+' : '' }}{{ number_format($diffSangat) }}
                    </td>
                    <td style="padding: 3px 4px; border: 1px solid #bdc3c7; text-align: center; background-color: #f8f9fa; color: {{ $colorSangat }}; font-weight: bold;">
                        {{ $pctSangat >= 0 ? '+' : '' }}{{ number_format($pctSangat, 2) }}%
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div style="background-color: #ecf0f1; padding: 8px; margin-bottom: 10px; border-left: 4px solid #3498db;">
        <strong style="font-size: 9px; color: #2c3e50;">RATA-RATA SKOR ARSIP INSTANSI</strong>
        <table cellpadding="5" style="margin-top: 5px; width: 100%;">
            <tr>
                <td style="width: 30%; font-size: 7px;"><strong>Sebelum:</strong> {{ number_format($avgSkorInstansiPrevious, 2) }}</td>
                <td style="width: 30%; font-size: 7px;"><strong>Sekarang:</strong> {{ number_format($avgSkorInstansiCurrent, 2) }}</td>
                <td style="width: 40%; font-size: 7px;"><strong>Perubahan:</strong> {{ number_format($avgSkorInstansiCurrent - $avgSkorInstansiPrevious, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Summary Stats -->
    <div class="summary-stats">
        <table cellpadding="8">
            <tr>
                <td style="width: 33.33%; background-color: #27ae60;">
                    <div style="font-size: 16px;">{{ $countNaik }}</div>
                    <div style="font-size: 7px;">Instansi Naik (Skor)</div>
                </td>
                <td style="width: 33.33%; background-color: #f39c12;">
                    <div style="font-size: 16px;">{{ $countStagnan }}</div>
                    <div style="font-size: 7px;">Instansi Stagnan (Skor)</div>
                </td>
                <td style="width: 33.33%; background-color: #e74c3c;">
                    <div style="font-size: 16px;">{{ $countTurun }}</div>
                    <div style="font-size: 7px;">Instansi Turun (Skor)</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Perubahan Kategori Kelengkapan -->
    <div style="margin-bottom: 15px;">
        <div style="background-color: #ecf0f1; padding: 6px 8px; margin-bottom: 8px; border-left: 4px solid #9b59b6;">
            <strong style="font-size: 9px; color: #2c3e50;">PERUBAHAN KATEGORI KELENGKAPAN</strong>
        </div>
        <table cellpadding="6">
            <tr>
                <td style="width: 33.33%; background-color: #27ae60; color: white; font-weight: bold; text-align: center;">
                    <div style="font-size: 14px;">{{ $countKategoriNaik }}</div>
                    <div style="font-size: 7px;">Naik Kategori</div>
                    <div style="font-size: 6px; margin-top: 2px; opacity: 0.9;">Contoh: Cukup → Lengkap</div>
                </td>
                <td style="width: 33.33%; background-color: #3498db; color: white; font-weight: bold; text-align: center;">
                    <div style="font-size: 14px;">{{ $countKategoriStagnan }}</div>
                    <div style="font-size: 7px;">Stagnan Kategori</div>
                    <div style="font-size: 6px; margin-top: 2px; opacity: 0.9;">Tetap di kategori sama</div>
                </td>
                <td style="width: 33.33%; background-color: #e74c3c; color: white; font-weight: bold; text-align: center;">
                    <div style="font-size: 14px;">{{ $countKategoriTurun }}</div>
                    <div style="font-size: 7px;">Turun Kategori</div>
                    <div style="font-size: 6px; margin-top: 2px; opacity: 0.9;">Contoh: Lengkap → Cukup</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Page Break Before Detail Table -->
    <div class="page-break"></div>

    <!-- Section Title for Detail Table -->
    <div class="section-title">DETAIL PERBANDINGAN SKOR PER INSTANSI</div>

    <!-- Data Table -->
    <table style="font-size: 5px;">
        <thead>
            <tr>
                <th width="15" rowspan="2" class="text-center">No</th>
                <th width="100" rowspan="2">Nama Instansi</th>
                <th width="30" rowspan="2" class="text-center">Skor<br>Sebelum</th>
                <th width="30" rowspan="2" class="text-center">Skor<br>Sekarang</th>
                <th colspan="5" class="text-center" style="background-color: #f39c12;">Kelengkapan Sebelum</th>
                <th colspan="5" class="text-center" style="background-color: #3498db;">Kelengkapan Sekarang</th>
                <th width="35" rowspan="2" class="text-center">Status</th>
            </tr>
            <tr>
                <th width="22" class="text-center" style="background-color: #f39c12;">Jml<br>ASN</th>
                <th width="22" class="text-center" style="background-color: #f39c12;">Sangat<br>Lengkap</th>
                <th width="22" class="text-center" style="background-color: #f39c12;">Lengkap</th>
                <th width="22" class="text-center" style="background-color: #f39c12;">Cukup<br>Lengkap</th>
                <th width="22" class="text-center" style="background-color: #f39c12;">Kurang<br>Lengkap</th>
                <th width="22" class="text-center" style="background-color: #3498db;">Jml<br>ASN</th>
                <th width="22" class="text-center" style="background-color: #3498db;">Sangat<br>Lengkap</th>
                <th width="22" class="text-center" style="background-color: #3498db;">Lengkap</th>
                <th width="22" class="text-center" style="background-color: #3498db;">Cukup<br>Lengkap</th>
                <th width="22" class="text-center" style="background-color: #3498db;">Kurang<br>Lengkap</th>
            </tr>
        </thead>
        <tbody>
            @foreach($changes as $index => $change)
            @php
                $statusClass = match($change['status']) {
                    'Naik' => 'status-naik',
                    'Turun' => 'status-turun',
                    'Stagnan' => 'status-stagnan',
                    default => ''
                };
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $change['nama_instansi'] }}</td>
                <td class="text-center">{{ number_format(floor($change['skor_sebelum'] * 10) / 10, 1) }}</td>
                <td class="text-center">
                    {{ number_format(floor($change['skor_sekarang'] * 10) / 10, 1) }}
                    @if($change['perubahan'] != 0)
                    <br><small style="color: {{ $change['perubahan'] > 0 ? '#27ae60' : '#e74c3c' }}; font-size: 4px;">
                        {{ $change['perubahan'] > 0 ? '+' : '' }}{{ number_format(floor($change['perubahan'] * 10) / 10, 1) }}
                    </small>
                    @endif
                </td>
                <td class="text-center">{{ number_format($change['jumlah_asn_sebelum']) }}</td>
                <td class="text-center">{{ number_format($change['sangat_lengkap_sebelum']) }}</td>
                <td class="text-center">{{ number_format($change['lengkap_sebelum']) }}</td>
                <td class="text-center">{{ number_format($change['cukup_lengkap_sebelum']) }}</td>
                <td class="text-center">{{ number_format($change['kurang_lengkap_sebelum']) }}</td>
                <td class="text-center">{{ number_format($change['jumlah_asn_sekarang']) }}</td>
                <td class="text-center">{{ number_format($change['sangat_lengkap_sekarang']) }}</td>
                <td class="text-center">{{ number_format($change['lengkap_sekarang']) }}</td>
                <td class="text-center">{{ number_format($change['cukup_lengkap_sekarang']) }}</td>
                <td class="text-center">{{ number_format($change['kurang_lengkap_sekarang']) }}</td>
                <td class="text-center">
                    <span class="status-badge {{ $statusClass }}">
                        {{ $change['status'] }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div style="margin-top: 15px; text-align: center; font-size: 7px; color: #7f8c8d; border-top: 1px solid #bdc3c7; padding-top: 8px;">
        <p style="margin: 0;">Dokumen ini digenerate secara otomatis oleh sistem Monev DMS</p>
        <p style="margin: 3px 0 0 0;">Badan Kepegawaian Negara - Direktorat Arsip Kepegawaian ASN</p>
    </div>
</body>
</html>
