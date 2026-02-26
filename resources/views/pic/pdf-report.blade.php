<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan PIC DMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 3px 0;
            font-size: 10px;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 3px;
        }
        .info-table td:first-child {
            width: 150px;
            font-weight: bold;
        }
        .stats-cards {
            margin: 15px 0;
            width: 100%;
        }
        .stats-cards td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            width: 25%;
        }
        .stats-cards .label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        .stats-cards .value {
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
        }
        td {
            font-size: 10px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            font-size: 9px;
            text-align: right;
            color: #666;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 8px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2>LAPORAN PERFORMA PIC DMS</h2>
        <p>Person In Charge - Document Management System</p>
        @if($dateFrom || $dateTo)
        <p style="margin-top: 5px;">
            Periode:
            @if($dateFrom && $dateTo)
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            @elseif($dateFrom)
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - Sekarang
            @else
                s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            @endif
        </p>
        @else
        <p style="margin-top: 5px;">Periode: Semua Data</p>
        @endif
    </div>

    <!-- Info PIC -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td>Ketua PIC DMS:</td>
                <td>{{ $pic->ketua->nama ?? '-' }} @if($pic->ketua) (NIP: {{ $pic->ketua->nip }}) @endif</td>
            </tr>
            <tr>
                <td>Status:</td>
                <td>{{ $pic->is_active ? 'Aktif' : 'Tidak Aktif' }}</td>
            </tr>
            <tr>
                <td>Jumlah Anggota:</td>
                <td>{{ $pic->anggota_count }} orang</td>
            </tr>
            <tr>
                <td>Jumlah Instansi:</td>
                <td>{{ $pic->instansi_count }} instansi</td>
            </tr>
        </table>
    </div>

    <!-- Statistik Performa Tim -->
    <div class="section-title">STATISTIK PERFORMA TIM</div>
    <table class="stats-cards">
        <tr>
            <td>
                <div class="label">Total Mapping + Inject</div>
                <div class="value">{{ number_format($stats['total_aktivitas']) }}</div>
            </td>
            <td>
                <div class="label">Total Mapping</div>
                <div class="value">{{ number_format($stats['total_mapping']) }}</div>
            </td>
            <td>
                <div class="label">Total Inject</div>
                <div class="value">{{ number_format($stats['total_inject']) }}</div>
            </td>
            <td>
                <div class="label">Unique PNS</div>
                <div class="value">{{ number_format($stats['unique_pns']) }}</div>
            </td>
        </tr>
    </table>

    <!-- Performa Individual -->
    <div class="section-title">PERFORMA INDIVIDUAL ANGGOTA</div>
    <table>
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>Nama</th>
                <th width="80" class="text-center">NIP</th>
                <th width="80" class="text-right">Total (M+I)</th>
                <th width="70" class="text-right">Mapping</th>
                <th width="60" class="text-right">Inject</th>
                <th width="70" class="text-right">Uniq PNS</th>
                <th width="70" class="text-right">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalTeamActivities = collect($performaAnggota)->sum('total_aktivitas') ?: 1;
            @endphp
            @forelse($performaAnggota as $index => $anggota)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $anggota->nama }}</td>
                <td class="text-center">{{ $anggota->nip }}</td>
                <td class="text-right">{{ number_format($anggota->total_aktivitas) }}</td>
                <td class="text-right">{{ number_format($anggota->total_mapping) }}</td>
                <td class="text-right">{{ number_format($anggota->total_inject) }}</td>
                <td class="text-right">{{ number_format($anggota->unique_pns) }}</td>
                <td class="text-right">{{ number_format(($anggota->total_aktivitas / $totalTeamActivities) * 100, 1) }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($performaAnggota) > 0)
        <tfoot style="background-color: #f0f0f0; font-weight: bold;">
            <tr>
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-right">{{ number_format(collect($performaAnggota)->sum('total_aktivitas')) }}</td>
                <td class="text-right">{{ number_format(collect($performaAnggota)->sum('total_mapping')) }}</td>
                <td class="text-right">{{ number_format(collect($performaAnggota)->sum('total_inject')) }}</td>
                <td class="text-right">-</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d F Y, H:i:s') }}</p>
        <p>Sistem Monitoring Direktorat Arsip Kepegawaian ASN - BKN</p>
    </div>
</body>
</html>
