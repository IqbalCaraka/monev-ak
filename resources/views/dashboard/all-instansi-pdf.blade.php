<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Semua Instansi - Monev DMS</title>
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
            font-size: 7px;
            border: 1px solid #2980b9;
        }

        table td {
            padding: 4px;
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
            font-size: 6px;
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
        <p style="margin-top: 8px; font-size: 12px; font-weight: bold;">DAFTAR SEMUA INSTANSI - MONEV DMS</p>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <div>
            <span class="label">Periode Penilaian:</span>
            <span>{{ \Carbon\Carbon::parse($monevDate)->format('d F Y') }}</span>
        </div>
        <div style="text-align: right;">
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

    <table style="width: 100%; margin-bottom: 12px; border-collapse: collapse;" cellpadding="6">
        <tr>
            <td style="width: 20%; text-align: center; background-color: #3498db; color: white; padding: 6px;">
                <div style="font-size: 14px; font-weight: bold;">{{ number_format($avgScore, 2) }}</div>
                <div style="font-size: 6px;">Rata-rata Skor</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #27ae60; color: white; padding: 6px;">
                <div style="font-size: 12px; font-weight: bold;">{{ $countSangatLengkap }}</div>
                <div style="font-size: 6px;">Sangat Lengkap</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #3498db; color: white; padding: 6px;">
                <div style="font-size: 12px; font-weight: bold;">{{ $countLengkap }}</div>
                <div style="font-size: 6px;">Lengkap</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #f39c12; color: white; padding: 6px;">
                <div style="font-size: 12px; font-weight: bold;">{{ $countCukupLengkap }}</div>
                <div style="font-size: 6px;">Cukup Lengkap</div>
            </td>
            <td style="width: 20%; text-align: center; background-color: #e74c3c; color: white; padding: 6px;">
                <div style="font-size: 12px; font-weight: bold;">{{ $countKurangLengkap }}</div>
                <div style="font-size: 6px;">Kurang Lengkap</div>
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th width="30" class="text-center">No</th>
                <th width="240">Nama Instansi</th>
                <th width="60" class="text-center">Kanreg</th>
                <th width="50" class="text-center">Skor</th>
                <th width="80" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($instansiList as $index => $instansi)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $instansi->nama_instansi }}</td>
                <td class="text-center">Kanreg {{ str_pad($instansi->kantor_regional_id ?? '0', 2, '0', STR_PAD_LEFT) }}</td>
                <td class="text-center">
                    <strong>{{ number_format(floor($instansi->monev_skor_instansi * 10) / 10, 1) }}</strong>
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
    <div style="margin-top: 15px; text-align: center; font-size: 7px; color: #7f8c8d; border-top: 1px solid #bdc3c7; padding-top: 8px;">
        <p style="margin: 0;">Dokumen ini digenerate secara otomatis oleh sistem Monev DMS</p>
        <p style="margin: 3px 0 0 0;">Badan Kepegawaian Negara - Direktorat Arsip Kepegawaian ASN</p>
    </div>
</body>
</html>
