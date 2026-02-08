<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StagingLogController extends Controller
{
    /**
     * Display list of NIP yang belum terdata
     */
    public function index()
    {
        // Get unique NIP dari staging dengan jumlah logs
        $stagingNips = DB::table('log_aktivitas_staging')
            ->select(
                'created_by_nip',
                'created_by_nama',
                DB::raw('COUNT(*) as total_logs'),
                DB::raw('MIN(created_at_log) as first_activity'),
                DB::raw('MAX(created_at_log) as last_activity')
            )
            ->groupBy('created_by_nip', 'created_by_nama')
            ->orderByDesc('total_logs')
            ->paginate(20);

        return view('statistik.staging-logs', compact('stagingNips'));
    }

    /**
     * Show logs untuk specific NIP di staging
     */
    public function show($nip)
    {
        // Get info pegawai dari staging
        $pegawaiInfo = DB::table('log_aktivitas_staging')
            ->where('created_by_nip', $nip)
            ->select('created_by_nip', 'created_by_nama')
            ->first();

        if (!$pegawaiInfo) {
            return redirect()->route('staging.index')
                ->with('error', 'NIP tidak ditemukan di staging');
        }

        // Get all logs untuk NIP ini
        $logs = DB::table('log_aktivitas_staging')
            ->where('created_by_nip', $nip)
            ->orderByDesc('created_at_log')
            ->paginate(50);

        $totalLogs = $logs->total();

        return view('statistik.staging-detail', compact('pegawaiInfo', 'logs', 'totalLogs'));
    }

    /**
     * Process logs dari staging ke main table setelah pegawai ditambahkan
     */
    public function process($nip)
    {
        // Cek apakah NIP sudah ada di table pegawai
        $pegawaiExists = DB::table('pegawai')->where('nip', $nip)->exists();

        if (!$pegawaiExists) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai dengan NIP ini belum ditambahkan ke database. Silakan tambahkan pegawai terlebih dahulu.'
            ], 400);
        }

        // Pindahkan logs dari staging ke log_aktivitas
        $logs = DB::table('log_aktivitas_staging')
            ->where('created_by_nip', $nip)
            ->get();

        if ($logs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada logs untuk NIP ini di staging'
            ], 404);
        }

        // Insert ke log_aktivitas
        $inserted = 0;
        foreach ($logs as $log) {
            DB::table('log_aktivitas')->insertOrIgnore([
                'id' => $log->id,
                'transaction_id' => $log->transaction_id,
                'event_name' => $log->event_name,
                'details' => $log->details,
                'created_by_id' => $log->created_by_id,
                'created_by_nama' => $log->created_by_nama,
                'created_by_nip' => $log->created_by_nip,
                'created_at_log' => $log->created_at_log,
                'object_pns_id' => $log->object_pns_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $inserted++;
        }

        // Hapus dari staging
        DB::table('log_aktivitas_staging')
            ->where('created_by_nip', $nip)
            ->delete();

        // Regenerate summary untuk NIP ini
        $this->regenerateSummaryForNip($nip);

        return response()->json([
            'success' => true,
            'message' => "{$inserted} logs berhasil diproses dan dipindahkan ke log aktivitas. Summary telah di-update."
        ]);
    }

    /**
     * Regenerate summary untuk specific NIP
     */
    private function regenerateSummaryForNip(string $nip)
    {
        // Hapus summary lama untuk NIP ini
        DB::table('pegawai_aktivitas_summary')
            ->where('nip', $nip)
            ->delete();

        // Generate summary baru
        $sql = "
            INSERT INTO pegawai_aktivitas_summary (nip, kategori_aktivitas, total_aktivitas, last_activity_at, created_at, updated_at)
            SELECT
                created_by_nip,
                CASE
                    WHEN event_name = 'unggah_dokumen' AND details != 'unggah_dokumen'
                        THEN 'Inject - Unggah Dokumen'
                    WHEN event_name = 'mapping_dokumen' AND details LIKE '%inject%'
                        THEN 'Inject - Mapping Dokumen'
                    WHEN event_name = 'unggah_dokumen' AND details = 'unggah_dokumen'
                        THEN 'Unggah Dokumen'
                    WHEN event_name = 'mapping_dokumen' AND (details NOT LIKE '%inject%' OR details IS NULL)
                        THEN 'Mapping Dokumen'
                    WHEN event_name = 'lock_arsip'
                        THEN 'Lock Arsip'
                    WHEN event_name = 'baca_arsip'
                        THEN 'Baca Arsip'
                    WHEN event_name = 'menambahkan_user'
                        THEN 'Menambahkan User'
                    WHEN event_name = 'menghapus_user'
                        THEN 'Menghapus User'
                    WHEN event_name = 'Laporan-Kekurangan-Riwayat'
                        THEN 'Laporan Kekurangan Riwayat'
                    ELSE CONCAT(UPPER(SUBSTRING(REPLACE(event_name, '_', ' '), 1, 1)),
                               LOWER(SUBSTRING(REPLACE(event_name, '_', ' '), 2)))
                END AS kategori_aktivitas,
                COUNT(*) as total_aktivitas,
                MAX(created_at_log) as last_activity_at,
                NOW() as created_at,
                NOW() as updated_at
            FROM log_aktivitas
            WHERE created_by_nip = ?
            GROUP BY created_by_nip, kategori_aktivitas
        ";

        DB::statement($sql, [$nip]);
    }
}
