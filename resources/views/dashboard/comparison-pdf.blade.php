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
            <span>{{ \Carbon\Carbon::now()->format('d F Y, H:i') }} WIB</span>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="summary-stats">
        <table cellpadding="8">
            <tr>
                <td style="width: 33.33%; background-color: #27ae60;">
                    <div style="font-size: 16px;">{{ $countNaik }}</div>
                    <div style="font-size: 7px;">Instansi Naik</div>
                </td>
                <td style="width: 33.33%; background-color: #f39c12;">
                    <div style="font-size: 16px;">{{ $countStagnan }}</div>
                    <div style="font-size: 7px;">Instansi Stagnan</div>
                </td>
                <td style="width: 33.33%; background-color: #e74c3c;">
                    <div style="font-size: 16px;">{{ $countTurun }}</div>
                    <div style="font-size: 7px;">Instansi Turun</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th width="25" class="text-center">No</th>
                <th width="200">Nama Instansi</th>
                <th width="70" class="text-center">{{ \Carbon\Carbon::parse($previousDate)->format('d M Y') }}</th>
                <th width="70" class="text-center">{{ \Carbon\Carbon::parse($currentDate)->format('d M Y') }}</th>
                <th width="60" class="text-center">Perubahan</th>
                <th width="60" class="text-center">Status</th>
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
                <td class="text-center">{{ number_format(ceil($change['skor_sebelum'] * 10) / 10, 1) }}</td>
                <td class="text-center">{{ number_format(ceil($change['skor_sekarang'] * 10) / 10, 1) }}</td>
                <td class="text-center">
                    <strong>
                        @if($change['perubahan'] > 0)
                            +{{ number_format(ceil($change['perubahan'] * 10) / 10, 1) }}
                        @else
                            {{ number_format(ceil($change['perubahan'] * 10) / 10, 1) }}
                        @endif
                    </strong>
                </td>
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
