<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Statistik Per Kantor Regional - Monev DMS</title>
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
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
            font-size: 8px;
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

        .status-sangat-lengkap {
            background-color: #27ae60;
        }

        .status-lengkap {
            background-color: #3498db;
        }

        .status-cukup-lengkap {
            background-color: #f39c12;
        }

        .status-kurang-lengkap {
            background-color: #e74c3c;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>BADAN KEPEGAWAIAN NEGARA</h1>
        <h2>Direktorat Arsip Kepegawaian ASN</h2>
        <p style="margin-top: 8px; font-size: 12px; font-weight: bold;">STATISTIK PER KANTOR REGIONAL - MONEV DMS</p>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <div>
            <span class="label">Periode Penilaian:</span>
            <span>{{ \Carbon\Carbon::parse($monevDate)->format('d F Y') }}</span>
        </div>
        <div style="text-align: right;">
            <span class="label">Waktu Cetak:</span>
            <span>{{ request('printed_at') ?? \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</span>
        </div>
        <div>
            <span class="label">Total Kantor Regional:</span>
            <span><strong>{{ $kanregStats->count() }} Kantor Regional</strong></span>
        </div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th width="25" class="text-center">No</th>
                <th width="80" class="text-center">Kantor Regional</th>
                <th width="60" class="text-center">Total Instansi</th>
                <th width="60" class="text-center">Rata-rata Skor</th>
                <th width="80" class="text-center">Status Kelengkapan</th>
                <th width="60" class="text-center">Sangat Lengkap</th>
                <th width="50" class="text-center">Lengkap</th>
                <th width="60" class="text-center">Cukup Lengkap</th>
                <th width="60" class="text-center">Kurang Lengkap</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kanregStats as $index => $kanreg)
            @php
                // Determine dominant status
                $statusCounts = [
                    'Sangat Lengkap' => $kanreg->count_sangat_lengkap,
                    'Lengkap' => $kanreg->count_lengkap,
                    'Cukup Lengkap' => $kanreg->count_cukup_lengkap,
                    'Kurang Lengkap' => $kanreg->count_kurang_lengkap
                ];
                arsort($statusCounts);
                $dominantStatus = array_key_first($statusCounts);

                $statusClass = match($dominantStatus) {
                    'Sangat Lengkap' => 'status-sangat-lengkap',
                    'Lengkap' => 'status-lengkap',
                    'Cukup Lengkap' => 'status-cukup-lengkap',
                    'Kurang Lengkap' => 'status-kurang-lengkap',
                    default => ''
                };
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center"><strong>Kantor Regional {{ str_pad($kanreg->kantor_regional_id, 2, '0', STR_PAD_LEFT) }}</strong></td>
                <td class="text-center">{{ $kanreg->total_instansi }}</td>
                <td class="text-center">
                    <strong>{{ number_format(floor($kanreg->rata_rata_skor * 10) / 10, 1) }}</strong>
                </td>
                <td class="text-center">
                    <span class="status-badge {{ $statusClass }}">
                        {{ $dominantStatus }}
                    </span>
                </td>
                <td class="text-center">{{ $kanreg->count_sangat_lengkap }}</td>
                <td class="text-center">{{ $kanreg->count_lengkap }}</td>
                <td class="text-center">{{ $kanreg->count_cukup_lengkap }}</td>
                <td class="text-center">{{ $kanreg->count_kurang_lengkap }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="background-color: #ecf0f1; font-weight: bold;">
            <tr>
                <td colspan="2" class="text-center"><strong>TOTAL</strong></td>
                <td class="text-center"><strong>{{ $kanregStats->sum('total_instansi') }}</strong></td>
                <td class="text-center"><strong>{{ number_format(floor($kanregStats->avg('rata_rata_skor') * 10) / 10, 1) }}</strong></td>
                <td></td>
                <td class="text-center"><strong>{{ $kanregStats->sum('count_sangat_lengkap') }}</strong></td>
                <td class="text-center"><strong>{{ $kanregStats->sum('count_lengkap') }}</strong></td>
                <td class="text-center"><strong>{{ $kanregStats->sum('count_cukup_lengkap') }}</strong></td>
                <td class="text-center"><strong>{{ $kanregStats->sum('count_kurang_lengkap') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Footer -->
    <div style="margin-top: 15px; text-align: center; font-size: 7px; color: #7f8c8d; border-top: 1px solid #bdc3c7; padding-top: 8px;">
        <p style="margin: 0;">Dokumen ini digenerate secara otomatis oleh sistem Monev DMS</p>
        <p style="margin: 3px 0 0 0;">Badan Kepegawaian Negara - Direktorat Arsip Kepegawaian ASN</p>
    </div>
</body>
</html>
