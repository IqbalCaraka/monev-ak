<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Aktivitas Pegawai - {{ $pegawai->nip }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px solid #4CAF50; }
        .header h1 { font-size: 16px; margin-bottom: 3px; color: #2c3e50; }
        .header h2 { font-size: 12px; font-weight: normal; color: #7f8c8d; }

        .pegawai-info { background-color: #f8f9fa; padding: 10px; margin-bottom: 15px; border-left: 4px solid #2196F3; }
        .pegawai-info .row { margin-bottom: 4px; }
        .pegawai-info .label { font-weight: bold; display: inline-block; width: 80px; }
        .pegawai-info .value { color: #2c3e50; }

        .period-info { text-align: center; background-color: #fff3e0; padding: 8px; margin-bottom: 15px; border-left: 4px solid #FF9800; }
        .period-info strong { color: #2c3e50; }

        .stats-box { background-color: #e3f2fd; padding: 12px; margin-bottom: 15px; text-align: center; border: 2px solid #2196F3; border-radius: 4px; }
        .stats-box h3 { font-size: 11px; color: #7f8c8d; margin-bottom: 4px; }
        .stats-box .stat-value { font-size: 24px; font-weight: bold; color: #2196F3; }

        .section-title { font-size: 13px; font-weight: bold; color: #2c3e50; margin-top: 15px; margin-bottom: 8px; padding: 6px; background-color: #e3f2fd; border-left: 4px solid #2196F3; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table thead { background-color: #2c3e50; color: white; }
        table th { padding: 6px; text-align: left; font-size: 9px; font-weight: bold; }
        table td { padding: 6px; border-bottom: 1px solid #dee2e6; font-size: 9px; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        table tbody tr:hover { background-color: #e9ecef; }

        .page-break { page-break-after: always; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #dee2e6; text-align: center; font-size: 8px; color: #7f8c8d; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN DETAIL AKTIVITAS PEGAWAI</h1>
        <h2>Direktorat Arsip Kepegawaian Aparatur Sipil Negara</h2>
    </div>

    <!-- Pegawai Info -->
    <div class="pegawai-info">
        <div class="row">
            <span class="label">NIP:</span>
            <span class="value">{{ $pegawai->nip }}</span>
        </div>
        <div class="row">
            <span class="label">Nama:</span>
            <span class="value">{{ $pegawai->nama }}</span>
        </div>
        @if(isset($pegawai->jabatan) && $pegawai->jabatan !== '-')
        <div class="row">
            <span class="label">Jabatan:</span>
            <span class="value">{{ $pegawai->jabatan }}</span>
        </div>
        @endif
    </div>

    <!-- Period Info -->
    <div class="period-info">
        <strong>Periode:</strong> {{ $periodText }}
    </div>

    <!-- Total Aktivitas -->
    <div class="stats-box">
        <h3>Total Aktivitas</h3>
        <div class="stat-value">{{ number_format($totalAktivitas) }}</div>
    </div>

    <!-- SECTION 1: RINGKASAN PER JENIS AKTIVITAS -->
    <div class="section-title">RINGKASAN PER JENIS AKTIVITAS</div>
    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="50%">Jenis Aktivitas</th>
                <th width="20%">Total</th>
                <th width="25%">Terakhir Aktivitas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailAktivitas as $index => $detail)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $detail->kategori_aktivitas }}</td>
                <td><strong>{{ number_format($detail->total_aktivitas) }}</strong></td>
                <td>{{ $detail->last_activity_at ? date('d M Y H:i', strtotime($detail->last_activity_at)) : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #999;">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- SECTION 2: BREAKDOWN PER HARI -->
    <div class="section-title">BREAKDOWN PER HARI</div>
    <table>
        <thead>
            <tr>
                <th width="20%">Tanggal</th>
                <th width="15%">Hari</th>
                <th width="45%">Jenis Aktivitas</th>
                <th width="20%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dailyBreakdown as $daily)
            <tr>
                <td>{{ date('d M Y', strtotime($daily->tanggal)) }}</td>
                <td>{{ $daily->hari }}</td>
                <td>{{ $daily->kategori_aktivitas }}</td>
                <td><strong>{{ number_format($daily->total) }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #999;">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- SECTION 3: BREAKDOWN PER MINGGU -->
    <div class="section-title">BREAKDOWN PER MINGGU</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Minggu Ke</th>
                <th width="25%">Rentang Tanggal</th>
                <th width="40%">Jenis Aktivitas</th>
                <th width="20%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($weeklyBreakdown as $weekly)
            <tr>
                <td>Minggu {{ $weekly->minggu_ke }}</td>
                <td>{{ $weekly->rentang_tanggal }}</td>
                <td>{{ $weekly->kategori_aktivitas }}</td>
                <td><strong>{{ number_format($weekly->total) }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #999;">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- SECTION 4: BREAKDOWN PER BULAN -->
    <div class="section-title">BREAKDOWN PER BULAN</div>
    <table>
        <thead>
            <tr>
                <th width="30%">Bulan</th>
                <th width="50%">Jenis Aktivitas</th>
                <th width="20%">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($monthlyBreakdown as $monthly)
            <tr>
                <td>{{ $monthly->bulan }}</td>
                <td>{{ $monthly->kategori_aktivitas }}</td>
                <td><strong>{{ number_format($monthly->total) }}</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #999;">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak pada: {{ date('d F Y H:i:s') }}</p>
        <p>Sistem Monitoring & Evaluasi - Direktorat Arsip Kepegawaian ASN</p>
    </div>
</body>
</html>
