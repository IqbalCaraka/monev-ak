<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Aktivitas Pegawai</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px solid #4CAF50; }
        .header h1 { font-size: 16px; margin-bottom: 3px; color: #2c3e50; }
        .header h2 { font-size: 12px; font-weight: normal; color: #7f8c8d; }
        .period-info { text-align: center; background-color: #f8f9fa; padding: 8px; margin-bottom: 15px; border-left: 4px solid #4CAF50; }
        .period-info strong { color: #2c3e50; }

        .stats-container { display: table; width: 100%; margin-bottom: 15px; }
        .stat-box { display: table-cell; width: 33.33%; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6; text-align: center; }
        .stat-box.primary { background-color: #e3f2fd; border-color: #2196F3; }
        .stat-box.success { background-color: #e8f5e9; border-color: #4CAF50; }
        .stat-box.warning { background-color: #fff3e0; border-color: #FF9800; }
        .stat-box h3 { font-size: 9px; color: #7f8c8d; margin-bottom: 4px; text-transform: uppercase; }
        .stat-box .stat-value { font-size: 20px; font-weight: bold; color: #2c3e50; }

        .section-title { font-size: 13px; font-weight: bold; color: #2c3e50; margin-top: 15px; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 2px solid #4CAF50; }

        .legend { margin-bottom: 10px; padding: 8px; background-color: #fff; border: 1px solid #dee2e6; }
        .legend-title { font-weight: bold; margin-bottom: 4px; font-size: 10px; }
        .legend-item { display: inline-block; margin-right: 12px; font-size: 9px; }
        .legend-color { display: inline-block; width: 12px; height: 12px; margin-right: 4px; vertical-align: middle; border-radius: 2px; }

        .chart-container { margin-bottom: 15px; page-break-inside: avoid; }
        .work-type-section { margin-bottom: 12px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #4CAF50; }
        .work-type-section.wfa { border-left-color: #2196F3; background-color: #e3f2fd; }
        .work-type-section.wfo { border-left-color: #4CAF50; background-color: #e8f5e9; }
        .work-type-section.libur { border-left-color: #9E9E9E; background-color: #f5f5f5; }
        .work-type-header { font-size: 11px; font-weight: bold; margin-bottom: 6px; color: #2c3e50; }

        .day-row { display: table; width: 100%; margin-bottom: 6px; }
        .day-label { display: table-cell; width: 60px; padding: 4px; font-weight: bold; vertical-align: middle; font-size: 10px; }
        .day-bar-container { display: table-cell; width: auto; vertical-align: middle; }

        /* Stack Bar */
        .stack-bar { height: 24px; width: 100%; border-radius: 3px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: table; }
        .bar-segment { display: table-cell; height: 24px; vertical-align: middle; text-align: center; font-weight: bold; font-size: 8px; color: white; }
        .bar-segment.mapping { background-color: #2196F3; }
        .bar-segment.inject { background-color: #FF5722; }

        .total-info { margin-top: 5px; font-size: 9px; color: #555; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table thead { background-color: #2c3e50; color: white; }
        table th { padding: 6px; text-align: left; font-size: 9px; font-weight: bold; }
        table td { padding: 6px; border-bottom: 1px solid #dee2e6; font-size: 9px; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }

        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #dee2e6; text-align: center; font-size: 8px; color: #7f8c8d; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN AKTIVITAS PEGAWAI</h1>
        <h2>Direktorat Arsip Kepegawaian Aparatur Sipil Negara</h2>
    </div>

    <div class="period-info">
        <strong>Periode:</strong> {{ $periodText }}
        @if($search)
            | <strong>Filter:</strong> {{ $search }}
        @endif
    </div>

    <div class="stats-container">
        <div class="stat-box primary">
            <h3>Total Pegawai</h3>
            <div class="stat-value">{{ number_format($stats['total_pegawai']) }}</div>
        </div>
        <div class="stat-box success">
            <h3>Total Aktivitas</h3>
            <div class="stat-value">{{ number_format($stats['total_aktivitas']) }}</div>
        </div>
        <div class="stat-box warning">
            <h3>Rata-rata / Pegawai</h3>
            <div class="stat-value">{{ number_format($stats['avg_aktivitas'], 1) }}</div>
        </div>
    </div>

    <div class="section-title">Grafik Aktivitas: Mapping vs Inject per Hari</div>

    <div class="legend">
        <div class="legend-title">Keterangan:</div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #2196F3;"></span>
            <strong>WFA</strong> - Senin, Rabu
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #4CAF50;"></span>
            <strong>WFO</strong> - Selasa, Kamis, Jumat
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #9E9E9E;"></span>
            <strong>Libur</strong> - Sabtu, Minggu
        </div>
        <div class="legend-item" style="margin-left: 15px;">
            <span class="legend-color" style="background-color: #2196F3;"></span>
            Mapping
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background-color: #FF5722;"></span>
            Inject
        </div>
    </div>

    <div class="chart-container">
        @php
            $maxTotal = 1;
            foreach ($dailyActivities as $category) {
                foreach ($category as $day => $stats) {
                    $total = $stats['mapping'] + $stats['inject'];
                    if ($total > $maxTotal) $maxTotal = $total;
                }
            }
        @endphp

        <!-- WFA Section -->
        <div class="work-type-section wfa">
            <div class="work-type-header">WFA (Work From Anywhere)</div>
            @foreach($dailyActivities['WFA'] as $day => $stats)
                @php
                    $total = $stats['mapping'] + $stats['inject'];
                    $mappingPct = $total > 0 ? ($stats['mapping'] / $total * 100) : 0;
                    $injectPct = $total > 0 ? ($stats['inject'] / $total * 100) : 0;
                    $barWidth = $maxTotal > 0 ? ($total / $maxTotal * 100) : 0;
                @endphp
                <div class="day-row">
                    <div class="day-label">{{ $day }}</div>
                    <div class="day-bar-container">
                        <div class="stack-bar" style="width: {{ $barWidth }}%;">
                            @if($stats['mapping'] > 0)
                                <div class="bar-segment mapping" style="width: {{ $mappingPct }}%;">M: {{ number_format($stats['mapping']) }}</div>
                            @endif
                            @if($stats['inject'] > 0)
                                <div class="bar-segment inject" style="width: {{ $injectPct }}%;">I: {{ number_format($stats['inject']) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="total-info">
                Total WFA: {{ number_format(array_sum(array_column($dailyActivities['WFA'], 'mapping')) + array_sum(array_column($dailyActivities['WFA'], 'inject'))) }} aktivitas
                (Mapping: {{ number_format(array_sum(array_column($dailyActivities['WFA'], 'mapping'))) }},
                Inject: {{ number_format(array_sum(array_column($dailyActivities['WFA'], 'inject'))) }})
            </div>
        </div>

        <!-- WFO Section -->
        <div class="work-type-section wfo">
            <div class="work-type-header">WFO (Work From Office)</div>
            @foreach($dailyActivities['WFO'] as $day => $stats)
                @php
                    $total = $stats['mapping'] + $stats['inject'];
                    $mappingPct = $total > 0 ? ($stats['mapping'] / $total * 100) : 0;
                    $injectPct = $total > 0 ? ($stats['inject'] / $total * 100) : 0;
                    $barWidth = $maxTotal > 0 ? ($total / $maxTotal * 100) : 0;
                @endphp
                <div class="day-row">
                    <div class="day-label">{{ $day }}</div>
                    <div class="day-bar-container">
                        <div class="stack-bar" style="width: {{ $barWidth }}%;">
                            @if($stats['mapping'] > 0)
                                <div class="bar-segment mapping" style="width: {{ $mappingPct }}%;">M: {{ number_format($stats['mapping']) }}</div>
                            @endif
                            @if($stats['inject'] > 0)
                                <div class="bar-segment inject" style="width: {{ $injectPct }}%;">I: {{ number_format($stats['inject']) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="total-info">
                Total WFO: {{ number_format(array_sum(array_column($dailyActivities['WFO'], 'mapping')) + array_sum(array_column($dailyActivities['WFO'], 'inject'))) }} aktivitas
                (Mapping: {{ number_format(array_sum(array_column($dailyActivities['WFO'], 'mapping'))) }},
                Inject: {{ number_format(array_sum(array_column($dailyActivities['WFO'], 'inject'))) }})
            </div>
        </div>

        <!-- Libur Section -->
        <div class="work-type-section libur">
            <div class="work-type-header">Hari Libur</div>
            @foreach($dailyActivities['Libur'] as $day => $stats)
                @php
                    $total = $stats['mapping'] + $stats['inject'];
                    $mappingPct = $total > 0 ? ($stats['mapping'] / $total * 100) : 0;
                    $injectPct = $total > 0 ? ($stats['inject'] / $total * 100) : 0;
                    $barWidth = $maxTotal > 0 ? ($total / $maxTotal * 100) : 0;
                @endphp
                <div class="day-row">
                    <div class="day-label">{{ $day }}</div>
                    <div class="day-bar-container">
                        <div class="stack-bar" style="width: {{ $barWidth }}%;">
                            @if($stats['mapping'] > 0)
                                <div class="bar-segment mapping" style="width: {{ $mappingPct }}%;">M: {{ number_format($stats['mapping']) }}</div>
                            @endif
                            @if($stats['inject'] > 0)
                                <div class="bar-segment inject" style="width: {{ $injectPct }}%;">I: {{ number_format($stats['inject']) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="total-info">
                Total Libur: {{ number_format(array_sum(array_column($dailyActivities['Libur'], 'mapping')) + array_sum(array_column($dailyActivities['Libur'], 'inject'))) }} aktivitas
                (Mapping: {{ number_format(array_sum(array_column($dailyActivities['Libur'], 'mapping'))) }},
                Inject: {{ number_format(array_sum(array_column($dailyActivities['Libur'], 'inject'))) }})
            </div>
        </div>
    </div>

    <!-- Top 5 Kategori -->
    <div class="section-title">Top 5 Kategori Aktivitas</div>
    <table>
        <thead>
            <tr>
                <th width="40">No</th>
                <th>Kategori</th>
                <th width="100" style="text-align: right;">Total</th>
                <th width="80" style="text-align: right;">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topKategori as $index => $kategori)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $kategori->kategori_aktivitas ?? $kategori->kategori ?? 'N/A' }}</td>
                    <td style="text-align: right;">{{ number_format($kategori->total) }}</td>
                    <td style="text-align: right;">{{ number_format($kategori->percentage, 2) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Top 10 Pegawai -->
    <div class="section-title">Top 10 Pegawai Teraktif</div>
    <table>
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="120">NIP</th>
                <th>Nama</th>
                <th width="100" style="text-align: right;">Total Aktivitas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($aktivitas->take(10) as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->nip }}</td>
                    <td>{{ $item->nama }}</td>
                    <td style="text-align: right;">{{ number_format($item->total_aktivitas) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis pada {{ date('d F Y H:i:s') }}</p>
        <p>Direktorat Arsip Kepegawaian Aparatur Sipil Negara</p>
    </div>
</body>
</html>
