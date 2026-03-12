<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statistik Per Kantor Regional - Perbandingan Periode</title>
    <style>
        @page {
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            color: #2c3e50;
            font-weight: bold;
        }
        .header .subtitle {
            margin: 3px 0;
            font-size: 9px;
            color: #7f8c8d;
        }
        .info-box {
            background-color: #ecf0f1;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
            display: table;
            width: 100%;
        }
        .info-box div {
            display: table-cell;
            padding: 3px 8px;
            font-size: 8px;
        }
        .label {
            font-weight: bold;
            color: #34495e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        th {
            background-color: #3498db;
            color: white;
            padding: 6px 8px;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #2980b9;
        }
        td {
            padding: 5px 8px;
            border: 1px solid #bdc3c7;
            font-size: 8px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-naik {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-stagnan {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-turun {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>STATISTIK PER KANTOR REGIONAL</h1>
        <div class="subtitle">Analisis Perbandingan Periode - Monev DMS</div>
        <div class="subtitle">Badan Kepegawaian Negara</div>
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
            <span class="label">Total Kantor Regional:</span>
            <span><strong>{{ count($kanregStats) }} Kanreg</strong></span>
        </div>
        <div style="text-align: right;">
            <span class="label">Waktu Cetak:</span>
            <span>{{ \Carbon\Carbon::now()->format('d F Y, H:i') }} WIB</span>
        </div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th width="25" class="text-center">No</th>
                <th width="200">Kantor Regional</th>
                <th width="60" class="text-center">Total<br>Instansi</th>
                <th width="50" class="text-center">Naik</th>
                <th width="50" class="text-center">Stagnan</th>
                <th width="50" class="text-center">Turun</th>
                <th width="80" class="text-center">Rata-rata<br>Perubahan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($kanregStats as $index => $stat)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $stat['nama_kanreg'] }}</strong></td>
                <td class="text-center">{{ $stat['total_instansi'] }}</td>
                <td class="text-center">
                    <span class="status-naik">{{ $stat['naik'] }}</span>
                </td>
                <td class="text-center">
                    <span class="status-stagnan">{{ $stat['stagnan'] }}</span>
                </td>
                <td class="text-center">
                    <span class="status-turun">{{ $stat['turun'] }}</span>
                </td>
                <td class="text-center">
                    <strong>
                        @if($stat['avg_perubahan'] > 0)
                            +{{ number_format($stat['avg_perubahan'], 2) }}
                        @else
                            {{ number_format($stat['avg_perubahan'], 2) }}
                        @endif
                    </strong>
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
