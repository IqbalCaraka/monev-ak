<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan PIC DMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #212529;
            margin: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #2c3e50;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 2px 0;
            font-size: 9px;
            color: #6c757d;
        }
        .header .periode {
            margin-top: 5px;
            padding: 3px 8px;
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
            display: inline-block;
            font-weight: 600;
            color: #1976d2;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 15px;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-box td {
            padding: 3px 5px;
            font-size: 9px;
            vertical-align: middle;
        }
        .info-box td:first-child {
            width: 110px;
            font-weight: 600;
            color: #495057;
        }
        .info-box td:nth-child(3) {
            width: 100px;
            font-weight: 600;
            color: #495057;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 8px;
            font-weight: 600;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 3px;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-secondary {
            background-color: #6c757d;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            margin: 15px 0 8px 0;
            padding: 5px 8px;
            background-color: #e9ecef;
            border-left: 4px solid #495057;
            color: #212529;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            border: 1px solid #dee2e6;
        }
        table.data-table thead {
            background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
        }
        table.data-table th {
            font-weight: 600;
            font-size: 9px;
            padding: 6px 8px;
            border: 1px solid #adb5bd;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.data-table td {
            font-size: 9px;
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        table.data-table tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        table.data-table tbody tr:hover {
            background-color: #e9ecef;
        }
        .stats-table {
            margin-top: 5px;
            border: 1px solid #dee2e6;
        }
        .stats-table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            background-color: #fff;
        }
        .stats-table .stat-label {
            display: block;
            font-size: 8px;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 4px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .stats-table .stat-value {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #212529;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .text-muted {
            color: #6c757d;
        }
        .font-weight-bold {
            font-weight: 700;
        }
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #dee2e6;
            font-size: 8px;
            text-align: center;
            color: #6c757d;
        }
        small {
            font-size: 85%;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h2>Statistik Performa PIC DMS</h2>
        <p>Sistem Monitoring Direktorat Arsip Kepegawaian ASN - BKN</p>
        @if($dateFrom || $dateTo)
        <div class="periode">
            Periode:
            @if($dateFrom && $dateTo)
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            @elseif($dateFrom)
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - Sekarang
            @else
                s/d {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            @endif
        </div>
        @else
        <div class="periode">Periode: Semua Data</div>
        @endif
    </div>

    <!-- Info PIC -->
    <div class="info-box">
        <table>
            <tr>
                <td>Ketua PIC DMS:</td>
                <td><strong>{{ $pic->ketua->nama ?? '-' }}</strong> @if($pic->ketua) <span class="text-muted">({{ $pic->ketua->nip }})</span> @endif</td>
                <td>Status:</td>
                <td>
                    @if($pic->is_active)
                        <span class="badge badge-success">Aktif</span>
                    @else
                        <span class="badge badge-secondary">Tidak Aktif</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td>Jumlah Anggota:</td>
                <td><strong>{{ $pic->anggota_count }} orang</strong></td>
                <td>Jumlah Instansi:</td>
                <td><strong>{{ $pic->instansi_count }} instansi</strong></td>
            </tr>
        </table>
    </div>

    <!-- Statistik Tim -->
    <div class="section-title">Ringkasan Statistik Tim</div>
    <table class="data-table stats-table" style="width: 100%;">
        <tr>
            <td style="width: 25%;">
                <span class="stat-label">Total Aktivitas</span>
                <span class="stat-value">{{ number_format($stats['total_aktivitas']) }}</span>
            </td>
            <td style="width: 25%;">
                <span class="stat-label">Mapping</span>
                <span class="stat-value">{{ number_format($stats['total_mapping']) }}</span>
            </td>
            <td style="width: 25%;">
                <span class="stat-label">Inject</span>
                <span class="stat-value">{{ number_format($stats['total_inject']) }}</span>
            </td>
            <td style="width: 25%;">
                <span class="stat-label">Unique PNS</span>
                <span class="stat-value">{{ number_format($stats['unique_pns']) }}</span>
            </td>
        </tr>
    </table>

    <!-- Performa Individual -->
    <div class="section-title">Performa Individual Anggota</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th class="text-left">Nama</th>
                <th class="text-center" style="width: 85px;">NIP</th>
                <th class="text-right" style="width: 70px;">Total (M+I)</th>
                <th class="text-right" style="width: 60px;">Mapping</th>
                <th class="text-right" style="width: 55px;">Inject</th>
                <th class="text-right" style="width: 65px;">Uniq PNS</th>
                <th class="text-right" style="width: 70px;">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalTeamActivities = collect($performaAnggota)->sum('total_aktivitas') ?: 1;
            @endphp
            @forelse($performaAnggota as $index => $anggota)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-left"><strong>{{ $anggota->nama }}</strong></td>
                <td class="text-center"><small class="text-muted">{{ $anggota->nip }}</small></td>
                <td class="text-right font-weight-bold">{{ number_format($anggota->total_aktivitas) }}</td>
                <td class="text-right">{{ number_format($anggota->total_mapping) }}</td>
                <td class="text-right">{{ number_format($anggota->total_inject) }}</td>
                <td class="text-right">{{ number_format($anggota->unique_pns) }}</td>
                <td class="text-right">{{ number_format(($anggota->total_aktivitas / $totalTeamActivities) * 100, 1) }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted" style="padding: 15px;">Tidak ada data anggota</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d F Y, H:i:s') }} WIB</p>
    </div>
</body>
</html>
