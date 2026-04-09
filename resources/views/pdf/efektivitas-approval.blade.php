<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Efektivitas Approval Dokumen MyASN</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
        }
        h1 {
            text-align: center;
            font-size: 16pt;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        h2 {
            text-align: center;
            font-size: 12pt;
            margin-top: 0;
            margin-bottom: 20px;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 40%;
            background-color: #f5f5f5;
        }
        .highlight {
            background-color: #fffacd !important;
            font-weight: bold;
            font-size: 11pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .section-title {
            background-color: #2196F3;
            color: white;
            padding: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 12pt;
        }
        .small {
            font-size: 8pt;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Laporan Efektivitas Kerja</h1>
    <h2>Approval Dokumen MyASN</h2>

    <!-- Info Periode -->
    <p style="text-align: center; font-weight: bold; margin-bottom: 20px;">
        Periode: {{ date('d/m/Y', strtotime($date_from)) }} - {{ date('d/m/Y', strtotime($date_to)) }}
    </p>

    <!-- Ringkasan Total -->
    <div class="section-title">RINGKASAN TOTAL</div>
    <table class="info-table">
        <tr>
            <td>Periode</td>
            <td>{{ date('d/m/Y', strtotime($date_from)) }} - {{ date('d/m/Y', strtotime($date_to)) }}</td>
        </tr>
        <tr>
            <td>Total Hari Kerja (Senin-Jumat)</td>
            <td>{{ $total_working_days }} hari</td>
        </tr>
        <tr>
            <td>Total Jam Kerja</td>
            <td>{{ $total_working_hours }} jam (7.5 jam/hari)</td>
        </tr>
        <tr>
            <td>Total Approval Dokumen MyASN</td>
            <td>{{ number_format($total_approval, 0, ',', '.') }} dokumen</td>
        </tr>
        <tr class="highlight">
            <td>Efektivitas Total</td>
            <td>{{ $efektivitas_total }} dokumen/jam</td>
        </tr>
    </table>

    <!-- Metrik Detail -->
    <div class="section-title" style="background-color: #4CAF50; margin-top: 10px;">METRIK DETAIL</div>
    <table class="info-table">
        <tr>
            <td>Total Pegawai</td>
            <td>{{ number_format($total_pegawai, 0, ',', '.') }} pegawai</td>
        </tr>
        <tr>
            <td>Rata-rata per Pegawai</td>
            <td><strong>{{ number_format($avg_per_person, 2, ',', '.') }}</strong> approval/pegawai</td>
        </tr>
        <tr>
            <td>Waktu per Approval</td>
            <td><strong>{{ number_format($minutes_per_approval, 2, ',', '.') }}</strong> menit/approval</td>
        </tr>
        <tr>
            <td>Approval per Menit</td>
            <td><strong>{{ $approvals_per_minute }}</strong> approval/menit</td>
        </tr>
    </table>

    <!-- Page Break -->
    <div style="page-break-before: always;"></div>

    <!-- Ranking Pegawai -->
    <div class="section-title">RANKING PEGAWAI BERDASARKAN EFEKTIVITAS</div>
    <table>
        <thead>
            <tr>
                <th width="8%">No</th>
                <th width="22%">NIP</th>
                <th width="40%">Nama</th>
                <th width="15%">Total Approval</th>
                <th width="15%">Efektivitas<br><span style="font-size: 8pt; font-weight: normal;">(approval/jam)</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($efektivitas_per_pegawai as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item['nip'] }}</td>
                <td>{{ $item['nama'] }}</td>
                <td class="text-center">{{ number_format($item['total_approval'], 0, ',', '.') }}</td>
                <td class="text-center"><strong>{{ $item['efektivitas'] }}</strong></td>
            </tr>
            @endforeach
            @if(count($efektivitas_per_pegawai) == 0)
            <tr>
                <td colspan="5" class="text-center">Tidak ada data</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Efektivitas Per Minggu -->
    <div class="section-title">EFEKTIVITAS PER MINGGU (7 HARI)</div>
    <table>
        <thead>
            <tr>
                <th width="40%">Periode</th>
                <th width="15%">Hari Kerja</th>
                <th width="15%">Jam Kerja</th>
                <th width="15%">Total Approval</th>
                <th width="15%">Efektivitas<br><span style="font-size: 8pt; font-weight: normal;">(approval/jam)</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($efektivitas_per_minggu as $item)
            <tr>
                <td>{{ $item['periode'] }}</td>
                <td class="text-center">{{ $item['working_days'] }}</td>
                <td class="text-center">{{ number_format($item['working_hours'], 1, ',', '.') }}</td>
                <td class="text-center">{{ number_format($item['total_approval'], 0, ',', '.') }}</td>
                <td class="text-center"><strong>{{ $item['efektivitas'] }}</strong></td>
            </tr>
            @endforeach
            @if(count($efektivitas_per_minggu) == 0)
            <tr>
                <td colspan="5" class="text-center">Tidak ada data</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Page Break -->
    <div style="page-break-before: always;"></div>

    <!-- Efektivitas Per Hari -->
    <div class="section-title">EFEKTIVITAS PER HARI</div>
    <table>
        <thead>
            <tr>
                <th width="40%">Tanggal</th>
                <th width="15%">Hari Kerja</th>
                <th width="15%">Jam Kerja</th>
                <th width="15%">Total Approval</th>
                <th width="15%">Efektivitas<br><span style="font-size: 8pt; font-weight: normal;">(approval/jam)</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($efektivitas_per_hari as $item)
            <tr>
                <td>{{ $item['periode'] }}</td>
                <td class="text-center">{{ $item['working_days'] }}</td>
                <td class="text-center">{{ number_format($item['working_hours'], 1, ',', '.') }}</td>
                <td class="text-center">{{ number_format($item['total_approval'], 0, ',', '.') }}</td>
                <td class="text-center"><strong>{{ $item['efektivitas'] }}</strong></td>
            </tr>
            @endforeach
            @if(count($efektivitas_per_hari) == 0)
            <tr>
                <td colspan="5" class="text-center">Tidak ada data</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada {{ request('printed_at') ?? date('d/m/Y H:i:s') }} | Laporan Efektivitas Approval Dokumen MyASN</p>
    </div>
</body>
</html>
