<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan PIC DMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px solid #4CAF50; }
        .header h1 { font-size: 16px; margin-bottom: 3px; color: #2c3e50; }
        .header h2 { font-size: 12px; font-weight: normal; color: #7f8c8d; }
        .period-info { text-align: center; background-color: #f8f9fa; padding: 8px; margin-bottom: 15px; border-left: 4px solid #4CAF50; font-size: 10px; }
        .legend { margin-bottom: 12px; padding: 8px; background-color: #fff; border: 1px solid #dee2e6; font-size: 9px; }
        .legend-title { font-weight: bold; margin-bottom: 4px; }
        .legend-item { display: inline-block; margin-right: 12px; }
        .legend-color { display: inline-block; width: 12px; height: 12px; margin-right: 4px; vertical-align: middle; border-radius: 2px; }

        .pic-section { margin-bottom: 20px; page-break-inside: avoid; border: 1px solid #dee2e6; padding: 12px; background-color: #fff; }
        .pic-header { background-color: #2c3e50; color: white; padding: 10px; margin: -12px -12px 12px -12px; }
        .pic-title { font-size: 13px; font-weight: bold; margin-bottom: 3px; }
        .pic-subtitle { font-size: 10px; opacity: 0.9; }

        .members-box { background-color: #f8f9fa; padding: 8px; border: 1px solid #dee2e6; margin-bottom: 12px; }
        .members-title { font-weight: bold; font-size: 11px; margin-bottom: 6px; color: #2c3e50; }
        .member-item { padding: 4px 0; border-bottom: 1px dashed #dee2e6; font-size: 8px; }
        .member-item:last-child { border-bottom: none; }
        .member-name { font-weight: bold; }
        .member-stats { color: #7f8c8d; margin-top: 2px; }

        .work-breakdown { margin-bottom: 8px; }
        .work-breakdown-title { font-weight: bold; font-size: 11px; margin-bottom: 8px; color: #2c3e50; border-bottom: 2px solid #4CAF50; padding-bottom: 4px; }
        .work-cat-section { margin-bottom: 10px; padding: 8px; border-left: 4px solid #4CAF50; background-color: #f8f9fa; }
        .work-cat-section.wfa { border-left-color: #2196F3; background-color: #e3f2fd; }
        .work-cat-section.wfo { border-left-color: #4CAF50; background-color: #e8f5e9; }
        .work-cat-section.libur { border-left-color: #9E9E9E; background-color: #f5f5f5; }
        .work-cat-header { font-weight: bold; font-size: 10px; margin-bottom: 6px; color: #2c3e50; }

        .day-row { display: table; width: 100%; margin-bottom: 5px; }
        .day-label { display: table-cell; width: 60px; padding: 3px; font-weight: bold; vertical-align: middle; font-size: 9px; }
        .day-bar-container { display: table-cell; width: auto; vertical-align: middle; }

        /* Stack Bar */
        .stack-bar { height: 20px; width: 100%; border-radius: 3px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: table; }
        .bar-segment { display: table-cell; height: 20px; vertical-align: middle; text-align: center; font-weight: bold; font-size: 7px; color: white; }
        .bar-segment.mapping { background-color: #2196F3; }
        .bar-segment.inject { background-color: #FF5722; }

        .total-info { margin-top: 4px; font-size: 8px; color: #555; font-weight: bold; }

        .summary-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 8px; }
        .summary-table thead { background-color: #2c3e50; color: white; }
        .summary-table th { padding: 6px; text-align: left; font-size: 8px; font-weight: bold; }
        .summary-table td { padding: 6px; border-bottom: 1px solid #dee2e6; font-size: 8px; }
        .summary-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #dee2e6; text-align: center; font-size: 8px; color: #7f8c8d; }
        .page-break { page-break-after: always; }
        .no-data { text-align: center; padding: 20px; color: #7f8c8d; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PERFORMA PIC DMS</h1>
        <h2>Direktorat Arsip Kepegawaian Aparatur Sipil Negara</h2>
    </div>

    <div class="period-info">
        <strong>Periode:</strong> {{ $periodText }}
    </div>

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
            <span class="stat-mapping">●</span> Mapping
        </div>
        <div class="legend-item">
            <span class="stat-inject">●</span> Inject
        </div>
    </div>

    @if($picStats->isEmpty())
        <div class="no-data">Tidak ada data PIC DMS untuk periode ini.</div>
    @else
        @foreach($picStats as $index => $pic)
            @if($index > 0 && $index % 3 == 0)
                <div class="page-break"></div>
            @endif

            <div class="pic-section">
                <div class="pic-header">
                    <div class="pic-title">{{ $pic->ketua_nama ?: 'Ketua Belum Ditentukan' }}</div>
                    <div class="pic-subtitle">
                        @if($pic->ketua_nip) NIP: {{ $pic->ketua_nip }} @else Tidak ada ketua @endif
                        | {{ $pic->total_anggota }} Anggota
                        | Total: {{ number_format($pic->total_aktivitas) }}
                        | Mapping: {{ number_format($pic->total_mapping) }}
                        | Inject: {{ number_format($pic->total_inject) }}
                    </div>
                </div>

                <!-- Members List -->
                <div class="members-box">
                    <div class="members-title">Daftar Anggota ({{ count($picMembers[$pic->pic_id] ?? []) }})</div>
                    @php $members = $picMembers[$pic->pic_id] ?? []; @endphp
                    @if(count($members) > 0)
                        @foreach($members as $member)
                            <div class="member-item">
                                <div class="member-name">{{ $member->nama }}</div>
                                <div class="member-stats">
                                    {{ $member->nip }} |
                                    Total: {{ number_format($member->total_aktivitas) }} |
                                    Mapping: {{ number_format($member->mapping_count) }} |
                                    Inject: {{ number_format($member->inject_count) }}
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="text-align: center; color: #7f8c8d; padding: 5px;">Tidak ada anggota</div>
                    @endif
                </div>

                <!-- Work Breakdown with Stack Bars -->
                <div class="work-breakdown">
                    <div class="work-breakdown-title">Grafik Aktivitas: Mapping vs Inject per Hari</div>
                    @php
                        $breakdown = $picWorkBreakdown[$pic->pic_id] ?? [
                            'WFA' => ['Senin' => ['mapping' => 0, 'inject' => 0], 'Rabu' => ['mapping' => 0, 'inject' => 0]],
                            'WFO' => ['Selasa' => ['mapping' => 0, 'inject' => 0], 'Kamis' => ['mapping' => 0, 'inject' => 0], 'Jumat' => ['mapping' => 0, 'inject' => 0]],
                            'Libur' => ['Sabtu' => ['mapping' => 0, 'inject' => 0], 'Minggu' => ['mapping' => 0, 'inject' => 0]]
                        ];

                        // Calculate max total for bar width scaling
                        $maxTotal = 1;
                        foreach ($breakdown as $category) {
                            foreach ($category as $day => $stats) {
                                $total = $stats['mapping'] + $stats['inject'];
                                if ($total > $maxTotal) $maxTotal = $total;
                            }
                        }
                    @endphp

                    <!-- WFA Section -->
                    <div class="work-cat-section wfa">
                        <div class="work-cat-header">WFA (Work From Anywhere)</div>
                        @foreach($breakdown['WFA'] as $day => $stats)
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
                            Total WFA: {{ number_format(array_sum(array_column($breakdown['WFA'], 'mapping')) + array_sum(array_column($breakdown['WFA'], 'inject'))) }} aktivitas
                            (Mapping: {{ number_format(array_sum(array_column($breakdown['WFA'], 'mapping'))) }},
                            Inject: {{ number_format(array_sum(array_column($breakdown['WFA'], 'inject'))) }})
                        </div>
                    </div>

                    <!-- WFO Section -->
                    <div class="work-cat-section wfo">
                        <div class="work-cat-header">WFO (Work From Office)</div>
                        @foreach($breakdown['WFO'] as $day => $stats)
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
                            Total WFO: {{ number_format(array_sum(array_column($breakdown['WFO'], 'mapping')) + array_sum(array_column($breakdown['WFO'], 'inject'))) }} aktivitas
                            (Mapping: {{ number_format(array_sum(array_column($breakdown['WFO'], 'mapping'))) }},
                            Inject: {{ number_format(array_sum(array_column($breakdown['WFO'], 'inject'))) }})
                        </div>
                    </div>

                    <!-- Libur Section -->
                    <div class="work-cat-section libur">
                        <div class="work-cat-header">Hari Libur</div>
                        @foreach($breakdown['Libur'] as $day => $stats)
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
                            Total Libur: {{ number_format(array_sum(array_column($breakdown['Libur'], 'mapping')) + array_sum(array_column($breakdown['Libur'], 'inject'))) }} aktivitas
                            (Mapping: {{ number_format(array_sum(array_column($breakdown['Libur'], 'mapping'))) }},
                            Inject: {{ number_format(array_sum(array_column($breakdown['Libur'], 'inject'))) }})
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Summary Table -->
        <div class="page-break"></div>
        <h3 style="font-size: 12px; margin-bottom: 8px; color: #2c3e50;">Ringkasan Performa Semua PIC</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th width="20">No</th>
                    <th>Ketua PIC DMS</th>
                    <th width="35" style="text-align: center;">Anggota</th>
                    <th width="55" style="text-align: right;">Total</th>
                    <th width="50" style="text-align: right;">Mapping</th>
                    <th width="45" style="text-align: right;">Inject</th>
                    <th width="55" style="text-align: right;">WFA</th>
                    <th width="55" style="text-align: right;">WFO</th>
                    <th width="45" style="text-align: right;">Libur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($picStats as $index => $pic)
                    @php
                        $breakdown = $picWorkBreakdown[$pic->pic_id] ?? [
                            'WFA' => ['Senin' => ['mapping' => 0, 'inject' => 0], 'Rabu' => ['mapping' => 0, 'inject' => 0]],
                            'WFO' => ['Selasa' => ['mapping' => 0, 'inject' => 0], 'Kamis' => ['mapping' => 0, 'inject' => 0], 'Jumat' => ['mapping' => 0, 'inject' => 0]],
                            'Libur' => ['Sabtu' => ['mapping' => 0, 'inject' => 0], 'Minggu' => ['mapping' => 0, 'inject' => 0]]
                        ];
                        $wfaTotal = array_sum(array_column($breakdown['WFA'], 'mapping')) + array_sum(array_column($breakdown['WFA'], 'inject'));
                        $wfoTotal = array_sum(array_column($breakdown['WFO'], 'mapping')) + array_sum(array_column($breakdown['WFO'], 'inject'));
                        $liburTotal = array_sum(array_column($breakdown['Libur'], 'mapping')) + array_sum(array_column($breakdown['Libur'], 'inject'));
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $pic->ketua_nama ?: 'Tidak ada ketua' }}</strong>
                            @if($pic->ketua_nip)
                                <br><small style="color: #7f8c8d;">{{ $pic->ketua_nip }}</small>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $pic->total_anggota }}</td>
                        <td style="text-align: right;">{{ number_format($pic->total_aktivitas) }}</td>
                        <td style="text-align: right;">{{ number_format($pic->total_mapping) }}</td>
                        <td style="text-align: right;">{{ number_format($pic->total_inject) }}</td>
                        <td style="text-align: right;">{{ number_format($wfaTotal) }}</td>
                        <td style="text-align: right;">{{ number_format($wfoTotal) }}</td>
                        <td style="text-align: right;">{{ number_format($liburTotal) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>Laporan ini digenerate secara otomatis pada {{ date('d F Y H:i:s') }}</p>
        <p>Direktorat Arsip Kepegawaian Aparatur Sipil Negara</p>
    </div>
</body>
</html>
