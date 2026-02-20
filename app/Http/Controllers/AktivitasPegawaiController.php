<?php

namespace App\Http\Controllers;

use App\Models\PegawaiAktivitasSummary;
use App\Models\Pegawai;
use App\Models\Pic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AktivitasPegawaiController extends Controller
{
    /**
     * Display statistics of pegawai activities
     *
     * OPTIMIZED: Support date filtering with composite index
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // OPTIMIZATION: Jika ada filter tanggal, query langsung dari log_aktivitas
        // dengan index optimization. Jika tidak, pakai summary table yang lebih cepat.
        if ($dateFrom || $dateTo) {
            // Dynamic aggregation dengan date filter (OPTIMIZED dengan index)
            $aktivitas = $this->getFilteredActivities($search, $dateFrom, $dateTo);
            $topKategori = $this->getTopKategoriFiltered($dateFrom, $dateTo);
            $stats = $this->getStatsFiltered($dateFrom, $dateTo);
            $mappingDokumen = $this->getMappingDokumenSummary($dateFrom, $dateTo, $search);
            $injectDokumen = $this->getInjectDokumenSummary($dateFrom, $dateTo, $search);
            $picStats = $this->getPicStatsSummary($dateFrom, $dateTo);
        } else {
            // Default: pakai summary table (sangat cepat)
            $aktivitas = $this->getActivitiesFromSummary($search);
            $topKategori = $this->getTopKategoriFromSummary();
            $stats = $this->getStatsFromSummary();
            $mappingDokumen = $this->getMappingDokumenSummary(null, null, $search);
            $injectDokumen = $this->getInjectDokumenSummary(null, null, $search);
            $picStats = $this->getPicStatsSummary(null, null);
        }

        return view('statistik.aktivitas-pegawai', compact('aktivitas', 'topKategori', 'stats', 'search', 'dateFrom', 'dateTo', 'mappingDokumen', 'injectDokumen', 'picStats'));
    }

    /**
     * Get activities from summary table (no date filter)
     * OPTIMIZED: Hitung unique PNS per pegawai menggunakan subquery
     */
    private function getActivitiesFromSummary($search = null)
    {
        $query = DB::table('pegawai_aktivitas_summary as pas')
            ->leftJoin('pegawai as p', 'pas.nip', '=', 'p.nip')
            ->select(
                'pas.nip',
                DB::raw('COALESCE(p.nama, pas.nip) as nama'),
                DB::raw('SUM(pas.total_aktivitas) as total_aktivitas'),
                DB::raw('MAX(pas.last_activity_at) as last_activity'),
                DB::raw('(SELECT COUNT(DISTINCT la.object_pns_id) FROM log_aktivitas la WHERE la.created_by_nip = pas.nip) as jenis_aktivitas')
            )
            ->groupBy('pas.nip', 'p.nama');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('pas.nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('total_aktivitas')->paginate(20);
    }

    /**
     * Get activities with date filter (OPTIMIZED with composite index)
     */
    private function getFilteredActivities($search = null, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('log_aktivitas as la')
            ->leftJoin('pegawai as p', 'la.created_by_nip', '=', 'p.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(p.nama, la.created_by_nama) as nama'),
                DB::raw('COUNT(*) as total_aktivitas'),
                DB::raw('MAX(la.created_at_log) as last_activity'),
                DB::raw('COUNT(DISTINCT la.event_name) as jenis_aktivitas')
            )
            ->whereNotNull('la.created_by_nip');

        // Date filter dengan index optimization
        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('la.created_by_nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%")
                  ->orWhere('la.created_by_nama', 'like', "%{$search}%");
            });
        }

        return $query->groupBy('la.created_by_nip', 'p.nama', 'la.created_by_nama')
                     ->orderByDesc('total_aktivitas')
                     ->paginate(20);
    }

    /**
     * Get top categories from summary table
     */
    private function getTopKategoriFromSummary()
    {
        return DB::table('pegawai_aktivitas_summary')
            ->select('kategori_aktivitas', DB::raw('SUM(total_aktivitas) as total'))
            ->groupBy('kategori_aktivitas')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    /**
     * Get top categories with date filter (OPTIMIZED)
     */
    private function getTopKategoriFiltered($dateFrom = null, $dateTo = null)
    {
        $query = DB::table('log_aktivitas')
            ->selectRaw($this->getCategoryCase() . ' as kategori_aktivitas, COUNT(*) as total')
            ->whereNotNull('created_by_nip');

        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        return $query->groupBy('kategori_aktivitas')
                     ->orderByDesc('total')
                     ->limit(5)
                     ->get();
    }

    /**
     * Get statistics from summary table
     */
    private function getStatsFromSummary()
    {
        $logPeriod = DB::table('log_aktivitas')
            ->selectRaw('DATE_FORMAT(MIN(created_at_log), "%d/%m/%Y") as first_log, DATE_FORMAT(MAX(created_at_log), "%d/%m/%Y") as last_log')
            ->first();

        return [
            'total_pegawai' => DB::table('pegawai_aktivitas_summary')
                ->distinct('nip')
                ->count('nip'),
            'total_aktivitas' => DB::table('pegawai_aktivitas_summary')
                ->sum('total_aktivitas'),
            'total_kategori' => DB::table('pegawai_aktivitas_summary')
                ->distinct('kategori_aktivitas')
                ->count('kategori_aktivitas'),
            'total_inject' => DB::table('pegawai_aktivitas_summary')
                ->where('kategori_aktivitas', 'Inject - Unggah Dokumen')
                ->sum('total_aktivitas'),
            'pegawai_belum_terdata' => DB::table('log_aktivitas_staging')
                ->distinct('created_by_nip')
                ->count('created_by_nip'),
            'first_log' => $logPeriod->first_log ?? '-',
            'last_log' => $logPeriod->last_log ?? '-',
        ];
    }

    /**
     * Get statistics with date filter (OPTIMIZED)
     */
    private function getStatsFiltered($dateFrom = null, $dateTo = null)
    {
        $baseQuery = DB::table('log_aktivitas')->whereNotNull('created_by_nip');

        if ($dateFrom) {
            $baseQuery->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $baseQuery->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        // Clone query untuk berbagai stats
        $logPeriod = (clone $baseQuery)
            ->selectRaw('DATE_FORMAT(MIN(created_at_log), "%d/%m/%Y") as first_log, DATE_FORMAT(MAX(created_at_log), "%d/%m/%Y") as last_log')
            ->first();

        $totalPegawai = (clone $baseQuery)->distinct('created_by_nip')->count('created_by_nip');
        $totalAktivitas = (clone $baseQuery)->count();

        // Count kategori dengan CASE WHEN
        $totalKategori = (clone $baseQuery)
            ->selectRaw($this->getCategoryCase() . ' as kategori_aktivitas')
            ->groupBy('kategori_aktivitas')
            ->get()
            ->count();

        // Count inject activities (details LIKE '%inject%')
        // Inject bisa di mapping_dokumen atau unggah_dokumen
        $totalInject = DB::table('log_aktivitas')
            ->whereNotNull('created_by_nip')
            ->where(function($q) use ($dateFrom, $dateTo) {
                if ($dateFrom) {
                    $q->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
                }
                if ($dateTo) {
                    $q->where('created_at_log', '<=', $dateTo . ' 23:59:59');
                }
            })
            ->where('details', 'LIKE', '%inject%')
            ->count();

        return [
            'total_pegawai' => $totalPegawai,
            'total_aktivitas' => $totalAktivitas,
            'total_kategori' => $totalKategori,
            'total_inject' => $totalInject,
            'pegawai_belum_terdata' => DB::table('log_aktivitas_staging')
                ->distinct('created_by_nip')
                ->count('created_by_nip'),
            'first_log' => $logPeriod->first_log ?? '-',
            'last_log' => $logPeriod->last_log ?? '-',
        ];
    }

    /**
     * Helper: Get CASE WHEN for category classification
     * UPDATED: Inject detected via details LIKE '%inject%'
     */
    private function getCategoryCase(): string
    {
        return "
            CASE
                WHEN details LIKE '%inject%' OR details LIKE '%Inject%'
                    THEN 'Inject Dokumen'
                WHEN event_name = 'unggah_dokumen' AND details = 'unggah_dokumen'
                    THEN 'Unggah Dokumen'
                WHEN event_name = 'mapping_dokumen' AND (details NOT LIKE '%inject%' AND details NOT LIKE '%Inject%')
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
            END
        ";
    }

    /**
     * Show detail aktivitas for specific pegawai
     * OPTIMIZED: Support date filtering
     */
    public function show(Request $request, $nip)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get pegawai info (bisa dari table pegawai atau dari log jika tidak ada)
        $pegawai = Pegawai::where('nip', $nip)->first();

        if (!$pegawai) {
            // Ambil nama dari log_aktivitas jika pegawai tidak ada di master
            $logInfo = DB::table('log_aktivitas')
                ->where('created_by_nip', $nip)
                ->select('created_by_nama')
                ->first();

            $pegawai = (object) [
                'nip' => $nip,
                'nama' => $logInfo->created_by_nama ?? $nip,
                'jabatan' => '-',
                'golongan' => '-',
            ];
        }

        // Get detail aktivitas per kategori (dengan atau tanpa filter)
        if ($dateFrom || $dateTo) {
            // Dynamic aggregation dari log_aktivitas
            $detailAktivitas = $this->getDetailAktivitasFiltered($nip, $dateFrom, $dateTo);
            $totalAktivitas = $detailAktivitas->sum('total_aktivitas');
        } else {
            // Dari summary table
            $detailAktivitas = PegawaiAktivitasSummary::where('nip', $nip)
                ->orderByDesc('total_aktivitas')
                ->get();
            $totalAktivitas = $detailAktivitas->sum('total_aktivitas');
        }

        return view('statistik.detail-aktivitas', compact('pegawai', 'detailAktivitas', 'totalAktivitas', 'dateFrom', 'dateTo'));
    }

    /**
     * Get detail aktivitas per kategori with date filter
     */
    private function getDetailAktivitasFiltered($nip, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('log_aktivitas')
            ->selectRaw($this->getCategoryCase() . ' as kategori_aktivitas, COUNT(*) as total_aktivitas, MAX(created_at_log) as last_activity_at')
            ->where('created_by_nip', $nip);

        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        return $query->groupBy('kategori_aktivitas')
                     ->orderByDesc('total_aktivitas')
                     ->get();
    }

    /**
     * Show detail logs for specific kategori aktivitas
     * OPTIMIZED: Support date filtering
     */
    public function detailKategori(Request $request, $nip, $kategori)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get pegawai info
        $pegawai = Pegawai::where('nip', $nip)->first();

        if (!$pegawai) {
            $logInfo = DB::table('log_aktivitas')
                ->where('created_by_nip', $nip)
                ->select('created_by_nama')
                ->first();

            $pegawai = (object) [
                'nip' => $nip,
                'nama' => $logInfo->created_by_nama ?? $nip,
                'jabatan' => '-',
                'golongan' => '-',
            ];
        }

        // Get logs berdasarkan kategori
        $query = DB::table('log_aktivitas')
            ->where('created_by_nip', $nip);

        // Date filter
        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        if ($kategori === 'Inject Dokumen' || $kategori === 'Inject - Unggah Dokumen') {
            // Inject Dokumen: details contains 'inject' atau 'Inject'
            $query->where(function($q) {
                $q->where('details', 'LIKE', '%inject%')
                  ->orWhere('details', 'LIKE', '%Inject%');
            });
        } elseif ($kategori === 'Unggah Dokumen') {
            // Unggah Dokumen (normal): unggah_dokumen dengan details = "unggah_dokumen"
            $query->where('event_name', 'unggah_dokumen')
                  ->where('details', 'unggah_dokumen');
        } elseif ($kategori === 'Mapping Dokumen') {
            // Mapping Dokumen (non-inject): mapping_dokumen tanpa inject
            $query->where('event_name', 'mapping_dokumen')
                  ->where(function($q) {
                      $q->where(function($q2) {
                          $q2->where('details', 'NOT LIKE', '%inject%')
                             ->where('details', 'NOT LIKE', '%Inject%');
                      })->orWhereNull('details');
                  });
        } else {
            // Kategori lain: convert Title Case ke event_name asli
            $eventNameMapping = [
                'Lock Arsip' => 'lock_arsip',
                'Baca Arsip' => 'baca_arsip',
                'Menambahkan User' => 'menambahkan_user',
                'Menghapus User' => 'menghapus_user',
                'Laporan Kekurangan Riwayat' => 'Laporan-Kekurangan-Riwayat',
            ];

            // Cek apakah ada mapping khusus
            if (isset($eventNameMapping[$kategori])) {
                $eventName = $eventNameMapping[$kategori];
            } else {
                // Default: convert Title Case ke snake_case
                // Contoh: "Lock Arsip" -> "lock_arsip"
                $eventName = strtolower(str_replace(' ', '_', $kategori));
            }

            $query->where('event_name', $eventName);
        }

        $logs = $query->orderByDesc('created_at_log')
                      ->paginate(50);

        // Count total untuk kategori ini
        $totalLogs = $logs->total();

        return view('statistik.detail-kategori', compact('pegawai', 'kategori', 'logs', 'totalLogs', 'dateFrom', 'dateTo'));
    }

    /**
     * Upload CSV log aktivitas baru
     */
    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:102400', // Max 100MB
        ]);

        try {
            $file = $request->file('csv_file');
            $filename = 'log_activity_' . time() . '.csv';

            // Pastikan folder imports ada
            $importPath = storage_path('app/imports');
            if (!file_exists($importPath)) {
                mkdir($importPath, 0775, true);
            }

            // Simpan file langsung ke storage/app/imports
            $file->move($importPath, $filename);
            $csvFile = $importPath . '/' . $filename;

            // Dispatch job untuk background processing
            \App\Jobs\ImportLogAktivitasJob::dispatch($csvFile, $filename);

            return redirect()->route('aktivitas-pegawai.index')
                ->with('success', 'Upload berhasil! File sedang diproses di background. Refresh halaman setelah beberapa saat untuk melihat hasilnya.');

        } catch (\Exception $e) {
            return redirect()->route('aktivitas-pegawai.index')
                ->with('error', 'Upload gagal: ' . $e->getMessage());
        }
    }

    /**
     * OLD METHOD - Kept as backup, now using queue job
     */
    private function uploadCsvOld(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:51200', // Max 50MB
        ]);

        try {
            $file = $request->file('csv_file');
            $filename = 'log_activity_' . time() . '.csv';

            // Pastikan folder imports ada
            $importPath = storage_path('app/imports');
            if (!file_exists($importPath)) {
                mkdir($importPath, 0775, true);
            }

            // Simpan file langsung ke storage/app/imports
            $file->move($importPath, $filename);
            $csvFile = $importPath . '/' . $filename;

            // Get all valid NIPs from pegawai table
            $validNips = DB::table('pegawai')->pluck('nip')->toArray();
            $handle = fopen($csvFile, 'r');
            $header = true;

            $batchMain = [];
            $batchStaging = [];
            $countMain = 0;
            $countStaging = 0;
            $batchSize = 1000;

            while (($data = fgetcsv($handle)) !== false) {
                if ($header) {
                    $header = false;
                    continue;
                }

                if (count($data) < 9) continue;

                $id = str_replace("\xEF\xBB\xBF", '', trim($data[0]));
                if (empty($id)) continue;

                $nip = !empty($data[6]) ? trim($data[6]) : null;
                if (empty($nip)) continue;

                // Calculate day_name and work_category from created_at
                $dayName = null;
                $workCategory = null;
                if (!empty($data[7])) {
                    try {
                        $createdAt = \Carbon\Carbon::parse(trim($data[7]));
                        $dayName = $this->getDayNameFromDate($createdAt);
                        $workCategory = $this->getWorkCategoryFromDay($dayName);
                    } catch (\Exception $e) {
                        // If parsing fails, use current date
                        $dayName = $this->getDayNameFromDate(now());
                        $workCategory = $this->getWorkCategoryFromDay($dayName);
                    }
                }

                $record = [
                    'id' => $id,
                    'transaction_id' => !empty($data[1]) ? trim($data[1]) : null,
                    'event_name' => !empty($data[2]) ? trim($data[2]) : null,
                    'details' => !empty($data[3]) ? trim($data[3]) : null,
                    'created_by_id' => !empty($data[4]) ? trim($data[4]) : null,
                    'created_by_nama' => !empty($data[5]) ? trim($data[5]) : null,
                    'created_by_nip' => $nip,
                    'created_at_log' => !empty($data[7]) ? trim($data[7]) : null,
                    'object_pns_id' => !empty($data[8]) ? trim($data[8]) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'day_name' => $dayName,
                    'work_category' => $workCategory,
                ];

                if (in_array($nip, $validNips)) {
                    $batchMain[] = $record;
                } else {
                    $batchStaging[] = $record;
                }

                if (count($batchMain) >= $batchSize) {
                    $this->insertIgnoreBatch($batchMain, 'log_aktivitas');
                    $countMain += count($batchMain);
                    $batchMain = [];
                }

                if (count($batchStaging) >= $batchSize) {
                    $this->insertIgnoreBatch($batchStaging, 'log_aktivitas_staging');
                    $countStaging += count($batchStaging);
                    $batchStaging = [];
                }
            }

            // Insert remaining
            if (!empty($batchMain)) {
                $this->insertIgnoreBatch($batchMain, 'log_aktivitas');
                $countMain += count($batchMain);
            }

            if (!empty($batchStaging)) {
                $this->insertIgnoreBatch($batchStaging, 'log_aktivitas_staging');
                $countStaging += count($batchStaging);
            }

            fclose($handle);

            // Regenerate summary for ALL affected NIPs (lebih efisien dengan query langsung)
            if ($countMain > 0) {
                // Hapus summary lama untuk NIP yang terpengaruh
                $affectedNips = DB::table('log_aktivitas')
                    ->select('created_by_nip')
                    ->whereIn('id', collect($batchMain)->pluck('id'))
                    ->distinct()
                    ->pluck('created_by_nip');

                DB::table('pegawai_aktivitas_summary')
                    ->whereIn('nip', $affectedNips)
                    ->delete();

                // Regenerate summary untuk SEMUA NIP yang terpengaruh sekaligus
                // NOTE: Inject - Mapping Dokumen EXCLUDED from counting
                $sql = "
                    INSERT INTO pegawai_aktivitas_summary (nip, kategori_aktivitas, total_aktivitas, last_activity_at, created_at, updated_at)
                    SELECT
                        created_by_nip,
                        CASE
                            WHEN event_name = 'unggah_dokumen' AND details != 'unggah_dokumen'
                                THEN 'Inject - Unggah Dokumen'
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
                    WHERE created_by_nip IN (" . implode(',', array_fill(0, count($affectedNips), '?')) . ")
                        AND NOT (event_name = 'mapping_dokumen' AND details LIKE '%inject%')
                    GROUP BY created_by_nip, kategori_aktivitas
                ";

                DB::statement($sql, $affectedNips->toArray());
            }

            return redirect()->route('aktivitas-pegawai.index')
                ->with('success', "Upload berhasil! {$countMain} logs ditambahkan ke aktivitas, {$countStaging} logs masuk ke staging (pegawai belum terdata). Summary table telah di-update.");

        } catch (\Exception $e) {
            return redirect()->route('aktivitas-pegawai.index')
                ->with('error', 'Upload gagal: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Insert batch with INSERT IGNORE
     */
    private function insertIgnoreBatch(array $data, string $table): void
    {
        if (empty($data)) return;

        $columns = array_keys($data[0]);
        $columnList = implode(', ', $columns);
        $values = [];
        $bindings = [];

        foreach ($data as $row) {
            $placeholders = [];
            foreach ($row as $value) {
                $placeholders[] = '?';
                $bindings[] = $value;
            }
            $values[] = '(' . implode(', ', $placeholders) . ')';
        }

        $valuesList = implode(', ', $values);
        DB::statement("INSERT IGNORE INTO {$table} ({$columnList}) VALUES {$valuesList}", $bindings);
    }

    /**
     * Helper: Regenerate summary for specific NIP
     * NOTE: Inject - Mapping Dokumen EXCLUDED from counting
     */
    private function regenerateSummaryForNip(string $nip): void
    {
        DB::table('pegawai_aktivitas_summary')->where('nip', $nip)->delete();

        $sql = "
            INSERT INTO pegawai_aktivitas_summary (nip, kategori_aktivitas, total_aktivitas, last_activity_at, created_at, updated_at)
            SELECT
                created_by_nip,
                CASE
                    WHEN event_name = 'unggah_dokumen' AND details != 'unggah_dokumen'
                        THEN 'Inject - Unggah Dokumen'
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
                AND NOT (event_name = 'mapping_dokumen' AND details LIKE '%inject%')
            GROUP BY created_by_nip, kategori_aktivitas
        ";

        DB::statement($sql, [$nip]);
    }

    /**
     * Get Mapping Dokumen Summary (Non-Inject) - ALL PEGAWAI
     * HIGHLY OPTIMIZED: Using composite index and efficient aggregation
     *
     * Counts:
     * - Total mapping per dokumen (COUNT(*))
     * - Total unique PNS yang dipetakan (COUNT DISTINCT object_pns_id)
     */
    private function getMappingDokumenSummary($dateFrom = null, $dateTo = null, $search = null)
    {
        $query = DB::table('log_aktivitas as la')
            ->leftJoin('pegawai as p', 'la.created_by_nip', '=', 'p.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(p.nama, la.created_by_nama) as nama'),
                DB::raw('COUNT(*) as total_per_dokumen'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as total_per_object_pns')
            )
            ->where('la.event_name', 'mapping_dokumen')
            ->where(function($q) {
                $q->where('la.details', 'NOT LIKE', '%inject%')
                  ->orWhereNull('la.details');
            })
            ->whereNotNull('la.created_by_nip');

        // Date filter
        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('la.created_by_nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%")
                  ->orWhere('la.created_by_nama', 'like', "%{$search}%");
            });
        }

        return $query->groupBy('la.created_by_nip', 'p.nama', 'la.created_by_nama')
                     ->orderByDesc('total_per_dokumen')
                     ->paginate(20, ['*'], 'mapping_page'); // Custom page parameter
    }

    /**
     * Get Inject Dokumen Summary - ALL PEGAWAI
     * OPTIMIZED: Inject detected via details LIKE '%inject%'
     *
     * Counts:
     * - Total inject per dokumen (COUNT(*))
     * - Total unique PNS yang di-inject (COUNT DISTINCT object_pns_id)
     */
    private function getInjectDokumenSummary($dateFrom = null, $dateTo = null, $search = null)
    {
        $query = DB::table('log_aktivitas as la')
            ->leftJoin('pegawai as p', 'la.created_by_nip', '=', 'p.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(p.nama, la.created_by_nama) as nama'),
                DB::raw('COUNT(*) as total_per_dokumen'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as total_per_object_pns')
            )
            ->where('la.details', 'LIKE', '%inject%')
            ->whereNotNull('la.created_by_nip');

        // Date filter
        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('la.created_by_nip', 'like', "%{$search}%")
                  ->orWhere('p.nama', 'like', "%{$search}%")
                  ->orWhere('la.created_by_nama', 'like', "%{$search}%");
            });
        }

        return $query->groupBy('la.created_by_nip', 'p.nama', 'la.created_by_nama')
                     ->orderByDesc('total_per_dokumen')
                     ->paginate(20, ['*'], 'inject_page'); // Custom page parameter
    }

    /**
     * Get PIC DMS Statistics Summary
     */
    private function getPicStatsSummary($dateFrom = null, $dateTo = null)
    {
        $query = DB::table('pic_dms as pd')
            ->leftJoin('pegawai as ketua', 'pd.ketua_nip', '=', 'ketua.nip')
            ->leftJoin('pic_dms_pegawai as pdp', 'pd.id', '=', 'pdp.pic_dms_id')
            ->leftJoin('log_aktivitas as la', function($join) use ($dateFrom, $dateTo) {
                $join->on('pdp.pegawai_nip', '=', 'la.created_by_nip');

                if ($dateFrom) {
                    $join->where('la.created_at_log', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $join->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
                }
            })
            ->select(
                'pd.id as pic_id',
                'ketua.nama as ketua_nama',
                'ketua.nip as ketua_nip',
                'pd.is_active',
                DB::raw('COUNT(DISTINCT pdp.pegawai_nip) as total_anggota'),
                DB::raw('COUNT(la.id) as total_aktivitas'),
                DB::raw('COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.details NOT LIKE "%inject%" AND la.details NOT LIKE "%Inject%") THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.details LIKE "%inject%" OR la.details LIKE "%Inject%" THEN 1 END) as total_inject'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as unique_pns')
            )
            ->where('pd.is_active', true)
            ->groupBy('pd.id', 'ketua.nama', 'ketua.nip', 'pd.is_active')
            ->orderByDesc('total_aktivitas')
            ->paginate(10, ['*'], 'pic_page');

        return $query;
    }

    /**
     * Export PDF report with work type categorization
     */
    public function exportPdf(Request $request)
    {
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get activities data
        if ($dateFrom || $dateTo) {
            $aktivitas = $this->getFilteredActivities($search, $dateFrom, $dateTo);
            $topKategori = $this->getTopKategoriFiltered($dateFrom, $dateTo);
            $stats = $this->getStatsFiltered($dateFrom, $dateTo);
        } else {
            $aktivitas = $this->getActivitiesFromSummary($search);
            $topKategori = $this->getTopKategoriFromSummary();
            $stats = $this->getStatsFromSummary();
        }

        // Add avg_aktivitas calculation
        $stats['avg_aktivitas'] = $stats['total_pegawai'] > 0
            ? round($stats['total_aktivitas'] / $stats['total_pegawai'], 1)
            : 0;

        // Calculate percentage for each category
        $totalKategoriCount = $topKategori->sum('total');
        foreach ($topKategori as $kategori) {
            $kategori->percentage = $totalKategoriCount > 0
                ? round(($kategori->total / $totalKategoriCount) * 100, 2)
                : 0;
        }

        // Get daily activities breakdown with work type categorization
        $dailyActivities = $this->getDailyActivitiesWithWorkType($dateFrom, $dateTo);

        // Prepare date range text
        $periodText = 'Semua Periode';
        if ($dateFrom && $dateTo) {
            $periodText = date('d M Y', strtotime($dateFrom)) . ' - ' . date('d M Y', strtotime($dateTo));
        } elseif ($dateFrom) {
            $periodText = 'Dari ' . date('d M Y', strtotime($dateFrom));
        } elseif ($dateTo) {
            $periodText = 'Sampai ' . date('d M Y', strtotime($dateTo));
        }

        // Load PDF
        $pdf = \PDF::loadView('statistik.aktivitas-pegawai-pdf', compact(
            'aktivitas',
            'topKategori',
            'stats',
            'dailyActivities',
            'periodText',
            'dateFrom',
            'dateTo',
            'search'
        ));

        // Download PDF
        $filename = 'Laporan_Aktivitas_Pegawai_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export PDF report for PIC DMS with work type breakdown per PIC
     */
    public function exportPicPdf(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Get PIC Stats (all data, not paginated)
        $picStats = $this->getPicStatsForPdf($dateFrom, $dateTo);

        // Get work category breakdown for each PIC (Mapping vs Inject only)
        $picWorkBreakdown = [];
        $picMembers = [];
        foreach ($picStats as $pic) {
            $picWorkBreakdown[$pic->pic_id] = $this->getPicWorkCategoryBreakdownMappingInject($pic->pic_id, $dateFrom, $dateTo);
            $picMembers[$pic->pic_id] = $this->getPicMembers($pic->pic_id, $dateFrom, $dateTo);
        }

        // Prepare date range text
        $periodText = 'Semua Periode';
        if ($dateFrom && $dateTo) {
            $periodText = date('d M Y', strtotime($dateFrom)) . ' - ' . date('d M Y', strtotime($dateTo));
        } elseif ($dateFrom) {
            $periodText = 'Dari ' . date('d M Y', strtotime($dateFrom));
        } elseif ($dateTo) {
            $periodText = 'Sampai ' . date('d M Y', strtotime($dateTo));
        }

        // Load PDF
        $pdf = \PDF::loadView('statistik.pic-dms-pdf', compact(
            'picStats',
            'picWorkBreakdown',
            'picMembers',
            'periodText',
            'dateFrom',
            'dateTo'
        ));

        // Portrait orientation (default A4)
        $pdf->setPaper('a4');

        // Download PDF
        $filename = 'Laporan_PIC_DMS_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Get PIC stats without pagination for PDF export
     */
    private function getPicStatsForPdf($dateFrom = null, $dateTo = null)
    {
        // Note: "mapping" is in event_name, "inject" is in details column (e.g., "via Inject")
        $query = DB::table('pic_dms as pd')
            ->leftJoin('pegawai as ketua', 'pd.ketua_nip', '=', 'ketua.nip')
            ->leftJoin('pic_dms_pegawai as pdp', 'pd.id', '=', 'pdp.pic_dms_id')
            ->leftJoin('log_aktivitas as la', function($join) use ($dateFrom, $dateTo) {
                $join->on('pdp.pegawai_nip', '=', 'la.created_by_nip');

                if ($dateFrom) {
                    $join->where('la.created_at_log', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $join->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
                }
            })
            ->select(
                'pd.id as pic_id',
                'ketua.nama as ketua_nama',
                'ketua.nip as ketua_nip',
                'pd.is_active',
                DB::raw('COUNT(DISTINCT pdp.pegawai_nip) as total_anggota'),
                DB::raw('COUNT(la.id) as total_aktivitas'),
                DB::raw('COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.details NOT LIKE "%inject%" AND la.details NOT LIKE "%Inject%") THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.details LIKE "%inject%" OR la.details LIKE "%Inject%" THEN 1 END) as total_inject'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as unique_pns')
            )
            ->where('pd.is_active', true)
            ->groupBy('pd.id', 'ketua.nama', 'ketua.nip', 'pd.is_active')
            ->orderByDesc('total_aktivitas')
            ->get();

        return $query;
    }

    /**
     * Get work category breakdown (WFA/WFO/Libur) for specific PIC
     */
    private function getPicWorkCategoryBreakdown($picId, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('pic_dms_pegawai as pdp')
            ->join('log_aktivitas as la', 'pdp.pegawai_nip', '=', 'la.created_by_nip')
            ->where('pdp.pic_dms_id', $picId)
            ->whereNotNull('la.work_category')
            ->select(
                'la.work_category',
                'la.day_name',
                DB::raw('COUNT(*) as total')
            );

        // Apply date filters
        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $data = $query->groupBy('la.work_category', 'la.day_name')->get();

        // Initialize structure
        $breakdown = [
            'WFA' => ['Senin' => 0, 'Rabu' => 0],
            'WFO' => ['Selasa' => 0, 'Kamis' => 0, 'Jumat' => 0],
            'Libur' => ['Sabtu' => 0, 'Minggu' => 0]
        ];

        // Fill data
        foreach ($data as $item) {
            if (isset($breakdown[$item->work_category][$item->day_name])) {
                $breakdown[$item->work_category][$item->day_name] = $item->total;
            }
        }

        return $breakdown;
    }

    /**
     * Get work category breakdown with Mapping vs Inject breakdown
     * Only count Mapping and Inject activities
     */
    private function getPicWorkCategoryBreakdownMappingInject($picId, $dateFrom = null, $dateTo = null)
    {
        // Note: "mapping" is in event_name, "inject" is in details column (e.g., "via Inject")
        $query = DB::table('pic_dms_pegawai as pdp')
            ->join('log_aktivitas as la', 'pdp.pegawai_nip', '=', 'la.created_by_nip')
            ->where('pdp.pic_dms_id', $picId)
            ->whereNotNull('la.work_category')
            ->whereNotNull('la.event_name')
            ->select(
                'la.work_category',
                'la.day_name',
                DB::raw('SUM(CASE WHEN la.event_name = "mapping_dokumen" AND (la.details NOT LIKE "%inject%" AND la.details NOT LIKE "%Inject%") THEN 1 ELSE 0 END) as mapping_count'),
                DB::raw('SUM(CASE WHEN la.details LIKE "%inject%" OR la.details LIKE "%Inject%" THEN 1 ELSE 0 END) as inject_count')
            );

        // Apply date filters
        if ($dateFrom) {
            $query->where('la.created_at_log', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $data = $query->groupBy('la.work_category', 'la.day_name')->get();

        // Initialize structure
        $breakdown = [
            'WFA' => [
                'Senin' => ['mapping' => 0, 'inject' => 0],
                'Rabu' => ['mapping' => 0, 'inject' => 0]
            ],
            'WFO' => [
                'Selasa' => ['mapping' => 0, 'inject' => 0],
                'Kamis' => ['mapping' => 0, 'inject' => 0],
                'Jumat' => ['mapping' => 0, 'inject' => 0]
            ],
            'Libur' => [
                'Sabtu' => ['mapping' => 0, 'inject' => 0],
                'Minggu' => ['mapping' => 0, 'inject' => 0]
            ]
        ];

        // Fill data
        foreach ($data as $item) {
            if (isset($breakdown[$item->work_category][$item->day_name])) {
                $breakdown[$item->work_category][$item->day_name]['mapping'] = $item->mapping_count;
                $breakdown[$item->work_category][$item->day_name]['inject'] = $item->inject_count;
            }
        }

        return $breakdown;
    }

    /**
     * Get PIC members with their activity stats
     */
    private function getPicMembers($picId, $dateFrom = null, $dateTo = null)
    {
        // Note: "mapping" is in event_name, "inject" is in details column (e.g., "via Inject")
        $query = DB::table('pic_dms_pegawai as pdp')
            ->join('pegawai as p', 'pdp.pegawai_nip', '=', 'p.nip')
            ->leftJoin('log_aktivitas as la', function($join) use ($dateFrom, $dateTo) {
                $join->on('pdp.pegawai_nip', '=', 'la.created_by_nip');

                if ($dateFrom) {
                    $join->where('la.created_at_log', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $join->where('la.created_at_log', '<=', $dateTo . ' 23:59:59');
                }
            })
            ->where('pdp.pic_dms_id', $picId)
            ->select(
                'p.nip',
                'p.nama',
                DB::raw('COUNT(la.id) as total_aktivitas'),
                DB::raw('SUM(CASE WHEN la.event_name = "mapping_dokumen" AND (la.details NOT LIKE "%inject%" AND la.details NOT LIKE "%Inject%") THEN 1 ELSE 0 END) as mapping_count'),
                DB::raw('SUM(CASE WHEN la.details LIKE "%inject%" OR la.details LIKE "%Inject%" THEN 1 ELSE 0 END) as inject_count')
            )
            ->groupBy('p.nip', 'p.nama')
            ->orderByDesc('total_aktivitas')
            ->get();

        return $query;
    }

    /**
     * Get daily activities breakdown categorized by work type with Mapping vs Inject
     * OPTIMIZED: Uses indexed columns day_name and work_category for fast querying
     * WFA: Senin, Rabu
     * WFO: Selasa, Kamis, Jumat
     * Libur: Sabtu, Minggu
     */
    private function getDailyActivitiesWithWorkType($dateFrom = null, $dateTo = null)
    {
        // Query with Mapping vs Inject breakdown
        // Note: "mapping" is in event_name, "inject" is in details column (e.g., "via Inject")
        $query = DB::table('log_aktivitas')
            ->select(
                'day_name',
                'work_category',
                DB::raw('SUM(CASE WHEN event_name = "mapping_dokumen" AND (details NOT LIKE "%inject%" AND details NOT LIKE "%Inject%") THEN 1 ELSE 0 END) as mapping_count'),
                DB::raw('SUM(CASE WHEN details LIKE "%inject%" OR details LIKE "%Inject%" THEN 1 ELSE 0 END) as inject_count')
            )
            ->whereNotNull('day_name')
            ->whereNotNull('work_category');

        // Apply date filters
        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $dailyData = $query->groupBy('day_name', 'work_category')->get();

        // Initialize categorized structure with Mapping vs Inject
        $categorized = [
            'WFA' => [
                'Senin' => ['mapping' => 0, 'inject' => 0],
                'Rabu' => ['mapping' => 0, 'inject' => 0]
            ],
            'WFO' => [
                'Selasa' => ['mapping' => 0, 'inject' => 0],
                'Kamis' => ['mapping' => 0, 'inject' => 0],
                'Jumat' => ['mapping' => 0, 'inject' => 0]
            ],
            'Libur' => [
                'Sabtu' => ['mapping' => 0, 'inject' => 0],
                'Minggu' => ['mapping' => 0, 'inject' => 0]
            ]
        ];

        // Fill in the data
        foreach ($dailyData as $data) {
            if (isset($categorized[$data->work_category][$data->day_name])) {
                $categorized[$data->work_category][$data->day_name]['mapping'] = $data->mapping_count;
                $categorized[$data->work_category][$data->day_name]['inject'] = $data->inject_count;
            }
        }

        return $categorized;
    }

    /**
     * Get day name in Indonesian from Carbon date
     */
    private function getDayNameFromDate($date): string
    {
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu'
        ];

        return $days[$date->dayOfWeek] ?? 'Unknown';
    }

    /**
     * Get work category based on day name
     * WFA: Senin, Rabu
     * WFO: Selasa, Kamis, Jumat
     * Libur: Sabtu, Minggu
     */
    private function getWorkCategoryFromDay(string $dayName): string
    {
        $wfa = ['Senin', 'Rabu'];
        $wfo = ['Selasa', 'Kamis', 'Jumat'];
        $libur = ['Sabtu', 'Minggu'];

        if (in_array($dayName, $wfa)) {
            return 'WFA';
        } elseif (in_array($dayName, $wfo)) {
            return 'WFO';
        } elseif (in_array($dayName, $libur)) {
            return 'Libur';
        }

        return 'Unknown';
    }
}
