<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Monev Skor Instansi</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .header p {
            font-size: 10px;
            margin: 3px 0;
            color: #7f8c8d;
        }

        .info-box {
            background-color: #ecf0f1;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }

        .info-box .label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            width: 120px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }

        .stats-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .stat-box {
            display: table-cell;
            width: 24%;
            padding: 12px;
            text-align: center;
            border: 1px solid #bdc3c7;
            margin-right: 1%;
        }

        .stat-box:last-child {
            margin-right: 0;
        }

        .stat-box .number {
            font-size: 20px;
            font-weight: bold;
            margin: 8px 0;
        }

        .stat-box .label {
            font-size: 9px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .stat-box .range {
            font-size: 8px;
            color: #95a5a6;
        }

        .stat-box.success { border-left: 4px solid #27ae60; }
        .stat-box.success .number { color: #27ae60; }

        .stat-box.primary { border-left: 4px solid #3498db; }
        .stat-box.primary .number { color: #3498db; }

        .stat-box.warning { border-left: 4px solid #f39c12; }
        .stat-box.warning .number { color: #f39c12; }

        .stat-box.danger { border-left: 4px solid #e74c3c; }
        .stat-box.danger .number { color: #e74c3c; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background-color: #34495e;
            color: white;
            padding: 8px;
            font-size: 9px;
            text-align: left;
            font-weight: bold;
        }

        table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 9px;
        }

        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-success { background-color: #27ae60; color: white; }
        .badge-primary { background-color: #3498db; color: white; }
        .badge-danger { background-color: #e74c3c; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-warning { background-color: #f39c12; color: white; }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #7f8c8d;
            padding: 10px 0;
            border-top: 1px solid #bdc3c7;
        }

        .page-break {
            page-break-after: always;
        }

        .two-column {
            width: 48%;
            float: left;
            margin-right: 2%;
        }

        .two-column:last-child {
            margin-right: 0;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN MONITORING DAN EVALUASI SKOR INSTANSI</h1>
        <p>Badan Kepegawaian Negara</p>
        <p>Direktorat Arsip Kepegawaian ASN</p>
    </div>

    <!-- Info Period -->
    <div class="info-box">
        <div>
            <span class="label">Periode Data:</span>
            <span>{{ \Carbon\Carbon::parse($monevNasionalScore->upload_date)->format('d F Y') }}</span>
        </div>
        <div>
            <span class="label">Tanggal Cetak:</span>
            <span>{{ \Carbon\Carbon::now()->format('d F Y, H:i') }} WIB</span>
        </div>
        <div>
            <span class="label">Total Instansi:</span>
            <span>{{ number_format($monevNasionalScore->total_instansi) }} Instansi</span>
        </div>
    </div>

    <!-- National Statistics -->
    <div class="section-title">RINGKASAN SKOR NASIONAL</div>

    <div style="text-align: center; padding: 15px; background-color: #3498db; color: white; margin-bottom: 15px; border-radius: 4px;">
        <div style="font-size: 11px; margin-bottom: 5px;">Rata-rata Skor Nasional</div>
        <div style="font-size: 24px; font-weight: bold;">{{ number_format($monevNasionalScore->monev_skor_nasional, 2) }}</div>
        <div style="font-size: 9px; opacity: 0.8;">Dari {{ number_format($monevNasionalScore->total_instansi) }} Instansi</div>
    </div>

    <!-- Distribution Statistics -->
    <div class="section-title">DISTRIBUSI KELENGKAPAN INSTANSI</div>

    <div class="stats-row">
        <div class="stat-box success">
            <div class="label">Sangat Lengkap</div>
            <div class="number">{{ number_format($monevNasionalScore->count_sangat_lengkap) }}</div>
            <div class="range">Skor > 90</div>
            @if($monevNasionalScore->total_instansi > 0)
            <div class="range">{{ number_format(($monevNasionalScore->count_sangat_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</div>
            @endif
        </div>
        <div class="stat-box primary">
            <div class="label">Lengkap</div>
            <div class="number">{{ number_format($monevNasionalScore->count_lengkap) }}</div>
            <div class="range">Skor 55.6 - 90</div>
            @if($monevNasionalScore->total_instansi > 0)
            <div class="range">{{ number_format(($monevNasionalScore->count_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</div>
            @endif
        </div>
        <div class="stat-box warning">
            <div class="label">Cukup Lengkap</div>
            <div class="number">{{ number_format($monevNasionalScore->count_cukup_lengkap) }}</div>
            <div class="range">Skor 30 - 55.5</div>
            @if($monevNasionalScore->total_instansi > 0)
            <div class="range">{{ number_format(($monevNasionalScore->count_cukup_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</div>
            @endif
        </div>
        <div class="stat-box danger">
            <div class="label">Kurang Lengkap</div>
            <div class="number">{{ number_format($monevNasionalScore->count_kurang_lengkap) }}</div>
            <div class="range">Skor < 30</div>
            @if($monevNasionalScore->total_instansi > 0)
            <div class="range">{{ number_format(($monevNasionalScore->count_kurang_lengkap / $monevNasionalScore->total_instansi) * 100, 1) }}%</div>
            @endif
        </div>
    </div>

    <!-- Top & Bottom Instansi -->
    <div class="clearfix">
        <div class="two-column">
            <div class="section-title">TOP 5 INSTANSI TERBAIK</div>
            <table>
                <thead>
                    <tr>
                        <th width="30" class="text-center">#</th>
                        <th>Nama Instansi</th>
                        <th width="60" class="text-center">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monevTopInstansi as $index => $inst)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ Str::limit($inst->nama_instansi, 35) }}</td>
                        <td class="text-center">
                            <span class="badge badge-success">{{ number_format($inst->monev_skor_instansi, 2) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="two-column">
            <div class="section-title">TOP 5 INSTANSI PERLU PERHATIAN</div>
            <table>
                <thead>
                    <tr>
                        <th width="30" class="text-center">#</th>
                        <th>Nama Instansi</th>
                        <th width="60" class="text-center">Skor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monevBottomInstansi as $index => $inst)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ Str::limit($inst->nama_instansi, 35) }}</td>
                        <td class="text-center">
                            <span class="badge badge-danger">{{ number_format($inst->monev_skor_instansi, 2) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada data</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="clear: both;"></div>

    <!-- Kantor Regional Statistics -->
    @if($monevKantorRegionalStats->count() > 0)
    <div class="page-break"></div>

    <div class="section-title">STATISTIK PER KANTOR REGIONAL</div>
    <p style="font-size: 9px; color: #7f8c8d; margin-bottom: 10px;">
        Pengelompokan {{ $monevKantorRegionalStats->count() }} Kantor Regional berdasarkan rata-rata skor instansi
    </p>

    <table>
        <thead>
            <tr>
                <th width="30" class="text-center">#</th>
                <th width="80" class="text-center">Kantor Regional</th>
                <th width="80" class="text-center">Total Instansi</th>
                <th width="80" class="text-center">Rata-rata Skor</th>
                <th width="70" class="text-center">Sangat Lengkap</th>
                <th width="70" class="text-center">Lengkap</th>
                <th width="70" class="text-center">Cukup Lengkap</th>
                <th width="70" class="text-center">Kurang Lengkap</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monevKantorRegionalStats as $index => $kanreg)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">
                    <span class="badge badge-primary">Kanreg {{ $kanreg->kantor_regional_id }}</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-info">{{ number_format($kanreg->total_instansi) }}</span>
                </td>
                <td class="text-center">
                    <strong style="color: #3498db;">{{ number_format($kanreg->rata_rata_skor, 2) }}</strong>
                </td>
                <td class="text-center">
                    <span class="badge badge-success">{{ number_format($kanreg->count_sangat_lengkap) }}</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-primary">{{ number_format($kanreg->count_lengkap) }}</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-warning">{{ number_format($kanreg->count_cukup_lengkap) }}</span>
                </td>
                <td class="text-center">
                    <span class="badge badge-danger">{{ number_format($kanreg->count_kurang_lengkap) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Dokumen ini dicetak secara otomatis dari Sistem Monitoring DMS - Badan Kepegawaian Negara</p>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y, H:i:s') }} WIB</p>
    </div>
</body>
</html>
