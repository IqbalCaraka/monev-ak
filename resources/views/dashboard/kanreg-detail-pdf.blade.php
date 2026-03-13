<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Instansi Kantor Regional {{ $kanregId }}</title>
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
            font-size: 16px;
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .header p {
            font-size: 9px;
            margin: 3px 0;
            color: #7f8c8d;
        }

        .info-box {
            background-color: #ecf0f1;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }

        .info-box div {
            margin-bottom: 5px;
        }

        .info-box .label {
            font-weight: bold;
            color: #2c3e50;
            display: inline-block;
            width: 120px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th {
            background-color: #34495e;
            color: white;
            padding: 8px;
            font-size: 9px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2c3e50;
        }

        table td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            font-size: 9px;
        }

        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            color: white;
            display: inline-block;
        }

        .status-sangat-lengkap { background-color: #27ae60; }
        .status-lengkap { background-color: #3498db; }
        .status-cukup-lengkap { background-color: #f39c12; }
        .status-kurang-lengkap { background-color: #e74c3c; }

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

        .summary-box {
            background-color: #3498db;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
        }

        .summary-box .number {
            font-size: 20px;
            font-weight: bold;
        }

        .summary-box .label {
            font-size: 9px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>DAFTAR DETAIL INSTANSI PER KANTOR REGIONAL</h1>
        <p>Badan Kepegawaian Negara</p>
        <p>Direktorat Arsip Kepegawaian ASN</p>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <div>
            <span class="label">Kantor Regional:</span>
            <span><strong>{{ $kanregId }}</strong></span>
        </div>
        <div>
            <span class="label">Periode Penilaian:</span>
            <span>{{ \Carbon\Carbon::parse($monevDate)->format('d F Y') }}</span>
        </div>
        <div>
            <span class="label">Waktu Cetak:</span>
            <span>{{ \Carbon\Carbon::now()->format('d F Y, H:i') }} WIB</span>
        </div>
        <div>
            <span class="label">Total Instansi:</span>
            <span><strong>{{ $instansiList->count() }} Instansi</strong></span>
        </div>
    </div>

    <!-- Summary Stats -->
    @php
        $avgScore = $instansiList->avg('monev_skor_instansi');
        $countSangatLengkap = $instansiList->where('monev_status_kelengkapan', 'Sangat Lengkap')->count();
        $countLengkap = $instansiList->where('monev_status_kelengkapan', 'Lengkap')->count();
        $countCukupLengkap = $instansiList->where('monev_status_kelengkapan', 'Cukup Lengkap')->count();
        $countKurangLengkap = $instansiList->where('monev_status_kelengkapan', 'Kurang Lengkap')->count();
    @endphp

    <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;" cellpadding="8">
        <tr>
            <td style="width: 20%; text-align: center; background-color: #3498db; color: white; padding: 8px;">
                <div style="font-size: 18px; font-weight: bold;">{{ number_format($avgScore, 2) }}</div>
                <div style="font-size: 8px;">Rata-rata Skor</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #27ae60; color: white; padding: 8px;">
                <div style="font-size: 16px; font-weight: bold;">{{ $countSangatLengkap }}</div>
                <div style="font-size: 7px;">Sangat Lengkap</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #3498db; color: white; padding: 8px;">
                <div style="font-size: 16px; font-weight: bold;">{{ $countLengkap }}</div>
                <div style="font-size: 7px;">Lengkap</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #f39c12; color: white; padding: 8px;">
                <div style="font-size: 16px; font-weight: bold;">{{ $countCukupLengkap }}</div>
                <div style="font-size: 7px;">Cukup Lengkap</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #e74c3c; color: white; padding: 8px;">
                <div style="font-size: 16px; font-weight: bold;">{{ $countKurangLengkap }}</div>
                <div style="font-size: 7px;">Kurang Lengkap</div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th>Nama Instansi</th>
                <th width="60" class="text-center">Skor</th>
                <th width="100" class="text-center">Status Kelengkapan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($instansiList as $index => $instansi)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $instansi->nama_instansi }}</td>
                <td class="text-center">
                    <strong>{{ number_format(ceil($instansi->monev_skor_instansi * 10) / 10, 1) }}</strong>
                </td>
                <td class="text-center">
                    @php
                        $statusClass = '';
                        switch($instansi->monev_status_kelengkapan) {
                            case 'Sangat Lengkap':
                                $statusClass = 'status-sangat-lengkap';
                                break;
                            case 'Lengkap':
                                $statusClass = 'status-lengkap';
                                break;
                            case 'Cukup Lengkap':
                                $statusClass = 'status-cukup-lengkap';
                                break;
                            case 'Kurang Lengkap':
                                $statusClass = 'status-kurang-lengkap';
                                break;
                        }
                    @endphp
                    <span class="status-badge {{ $statusClass }}">
                        {{ $instansi->monev_status_kelengkapan }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Dokumen ini dicetak secara otomatis dari Sistem Monitoring DMS - Badan Kepegawaian Negara</p>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y, H:i:s') }} WIB</p>
    </div>
</body>
</html>
