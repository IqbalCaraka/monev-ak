<?php

namespace App\Http\Controllers;

use App\Models\PegawaiAktivitasSummary;
use App\Models\Pegawai;
use App\Models\Pic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

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

        // LAZY LOADING OPTIMIZATION: Hanya load stats cards dan top kategori
        // Tables (aktivitas, mapping, inject, pic) akan di-load via AJAX untuk performa lebih cepat
        if ($dateFrom || $dateTo) {
            $topKategori = $this->getTopKategoriFiltered($dateFrom, $dateTo);
            $stats = $this->getStatsFiltered($dateFrom, $dateTo);
        } else {
            $topKategori = $this->getTopKategoriFromSummary();
            $stats = $this->getStatsFromSummary();
        }

        // Tables akan di-load via AJAX, tidak perlu query di sini
        // Ini drastis mempercepat initial page load (6 queries → 2 queries)

        return view('statistik.aktivitas-pegawai', compact('topKategori', 'stats', 'search', 'dateFrom', 'dateTo'));
    }

    /**
     * Get activities from summary table (no date filter)
     * OPTIMIZED: Hitung jumlah jenis aktivitas (distinct kategori)
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
                DB::raw('COUNT(DISTINCT pas.kategori_aktivitas) as jenis_aktivitas')
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

        // Count inject activities (ONLY inject_type = 'unggah' for optimal performance)
        // HANYA hitung Inject Unggah, bukan Inject Mapping
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
            ->where('inject_type', 'unggah')
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
     * UPDATED: ONLY count inject_type = 'unggah' as Inject Dokumen
     */
    private function getCategoryCase(): string
    {
        return "
            CASE
                WHEN is_inject = 1 AND event_name = 'mapping_dokumen'
                    THEN 'Inject - Mapping Dokumen'
                WHEN is_inject = 1 AND event_name = 'unggah_dokumen'
                    THEN 'Inject - Unggah Dokumen'
                WHEN is_inject = 1
                    THEN 'Inject Dokumen'
                WHEN event_name = 'unggah_dokumen' AND (is_inject = 0 OR is_inject IS NULL)
                    THEN 'Unggah Dokumen'
                WHEN event_name = 'mapping_dokumen' AND (is_inject = 0 OR is_inject IS NULL)
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

        // Normalize kategori untuk case-insensitive comparison
        $kategoriLower = strtolower($kategori);

        if (in_array($kategoriLower, ['inject dokumen', 'inject - unggah dokumen', 'inject - mapping dokumen'])) {
            // OPTIMIZED: Use indexed column is_inject
            $query->where('is_inject', 1);

            // Additional filter for specific inject types
            if ($kategoriLower === 'inject - unggah dokumen') {
                $query->where('event_name', 'unggah_dokumen');
            } elseif ($kategoriLower === 'inject - mapping dokumen') {
                $query->where('event_name', 'mapping_dokumen');
            }
        } elseif ($kategoriLower === 'unggah dokumen') {
            // Unggah Dokumen (normal): unggah_dokumen TANPA inject
            $query->where('event_name', 'unggah_dokumen')
                  ->where(function($q) {
                      $q->where('is_inject', 0)
                        ->orWhereNull('is_inject');
                  });
        } elseif ($kategoriLower === 'mapping dokumen') {
            // Mapping Dokumen (non-inject): mapping_dokumen tanpa inject
            // OPTIMIZED: Use indexed column is_inject
            $query->where('event_name', 'mapping_dokumen')
                  ->where(function($q) {
                      $q->where('is_inject', 0)
                        ->orWhereNull('is_inject');
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
                            WHEN inject_type = 'unggah'
                                THEN 'Inject Dokumen'
                            WHEN event_name = 'unggah_dokumen' AND details = 'unggah_dokumen'
                                THEN 'Unggah Dokumen'
                            WHEN event_name = 'mapping_dokumen' AND inject_type IS NULL
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
                    WHEN inject_type = 'unggah'
                        THEN 'Inject Dokumen'
                    WHEN event_name = 'unggah_dokumen' AND details = 'unggah_dokumen'
                        THEN 'Unggah Dokumen'
                    WHEN event_name = 'mapping_dokumen' AND inject_type IS NULL
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

    /**
     * Get Mapping Dokumen Summary (Non-Inject) - ALL PEGAWAI
     * HIGHLY OPTIMIZED: Using composite index and indexed is_inject column
     *
     * Counts:
     * - Total mapping per dokumen (COUNT(*))
     * - Total unique PNS yang dipetakan (COUNT DISTINCT object_pns_id)
     * - Filters: is_inject = 0 OR NULL (uses index for fast filtering)
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
            // OPTIMIZED: Use indexed column is_inject instead of LIKE on details
            // This is MUCH FASTER and more accurate
            ->where(function($q) {
                $q->where('la.is_inject', 0)
                  ->orWhereNull('la.is_inject');
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
     * OPTIMIZED: Inject detected via indexed column is_inject = 1
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
            // OPTIMIZED: Use indexed column is_inject instead of LIKE on details
            // This is MUCH FASTER and more accurate
            ->where('la.is_inject', 1)
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
                DB::raw('COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.is_inject = 1 THEN 1 END) as total_inject'),
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
        // Note: "mapping" is in event_name, "inject" detected via indexed is_inject column
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
                DB::raw('MAX(ketua.nama) as ketua_nama'),
                DB::raw('MAX(ketua.nip) as ketua_nip'),
                'pd.is_active',
                DB::raw('COUNT(DISTINCT pdp.pegawai_nip) as total_anggota'),
                DB::raw('(COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) + COUNT(CASE WHEN la.is_inject = 1 THEN 1 END)) as total_aktivitas'),
                DB::raw('COUNT(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 END) as total_mapping'),
                DB::raw('COUNT(CASE WHEN la.is_inject = 1 THEN 1 END) as total_inject'),
                DB::raw('COUNT(DISTINCT la.object_pns_id) as unique_pns')
            )
            ->where('pd.is_active', true)
            ->groupBy('pd.id', 'pd.is_active')
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
        // Note: "mapping" is in event_name, "inject" detected via indexed is_inject column
        $query = DB::table('pic_dms_pegawai as pdp')
            ->join('log_aktivitas as la', 'pdp.pegawai_nip', '=', 'la.created_by_nip')
            ->where('pdp.pic_dms_id', $picId)
            ->whereNotNull('la.work_category')
            ->whereNotNull('la.event_name')
            ->select(
                'la.work_category',
                'la.day_name',
                DB::raw('SUM(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 ELSE 0 END) as mapping_count'),
                DB::raw('SUM(CASE WHEN la.is_inject = 1 THEN 1 ELSE 0 END) as inject_count')
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
        // Note: "mapping" is in event_name, "inject" detected via indexed is_inject column
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
                DB::raw('(SUM(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 ELSE 0 END) + SUM(CASE WHEN la.is_inject = 1 THEN 1 ELSE 0 END)) as total_aktivitas'),
                DB::raw('SUM(CASE WHEN la.event_name = "mapping_dokumen" AND (la.is_inject = 0 OR la.is_inject IS NULL) THEN 1 ELSE 0 END) as mapping_count'),
                DB::raw('SUM(CASE WHEN la.is_inject = 1 THEN 1 ELSE 0 END) as inject_count')
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
        // Note: "mapping" is in event_name, "inject" detected via indexed is_inject column
        $query = DB::table('log_aktivitas')
            ->select(
                'day_name',
                'work_category',
                DB::raw('SUM(CASE WHEN event_name = "mapping_dokumen" AND (is_inject = 0 OR is_inject IS NULL) THEN 1 ELSE 0 END) as mapping_count'),
                DB::raw('SUM(CASE WHEN is_inject = 1 THEN 1 ELSE 0 END) as inject_count')
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

    /**
     * Export detail aktivitas pegawai ke Excel dengan breakdown per hari/minggu/bulan
     */
    public function exportPegawaiExcel(Request $request, $nip)
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

        // Get aktivitas breakdown
        $dailyBreakdown = $this->getPegawaiDailyBreakdown($nip, $dateFrom, $dateTo);
        $weeklyBreakdown = $this->getPegawaiWeeklyBreakdown($nip, $dateFrom, $dateTo);
        $monthlyBreakdown = $this->getPegawaiMonthlyBreakdown($nip, $dateFrom, $dateTo);
        $detailAktivitas = $dateFrom || $dateTo
            ? $this->getDetailAktivitasFiltered($nip, $dateFrom, $dateTo)
            : PegawaiAktivitasSummary::where('nip', $nip)->orderByDesc('total_aktivitas')->get();

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header Info
        $sheet->setCellValue('A1', 'LAPORAN AKTIVITAS PEGAWAI');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'NIP: ' . $pegawai->nip);
        $sheet->setCellValue('A3', 'Nama: ' . $pegawai->nama);
        $periodText = 'Periode: ';
        if ($dateFrom && $dateTo) {
            $periodText .= date('d M Y', strtotime($dateFrom)) . ' - ' . date('d M Y', strtotime($dateTo));
        } elseif ($dateFrom) {
            $periodText .= 'Dari ' . date('d M Y', strtotime($dateFrom));
        } elseif ($dateTo) {
            $periodText .= 'Sampai ' . date('d M Y', strtotime($dateTo));
        } else {
            $periodText .= 'Semua Periode';
        }
        $sheet->setCellValue('A4', $periodText);

        $row = 6;

        // SECTION 1: RINGKASAN PER JENIS AKTIVITAS
        $sheet->setCellValue('A' . $row, 'RINGKASAN PER JENIS AKTIVITAS');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E3F2FD');
        $row++;

        $sheet->setCellValue('A' . $row, 'Jenis Aktivitas');
        $sheet->setCellValue('B' . $row, 'Total');
        $sheet->setCellValue('C' . $row, 'Terakhir Aktivitas');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($detailAktivitas as $detail) {
            $sheet->setCellValue('A' . $row, $detail->kategori_aktivitas);
            $sheet->setCellValue('B' . $row, $detail->total_aktivitas);
            $sheet->setCellValue('C' . $row, $detail->last_activity_at ? date('d M Y H:i', strtotime($detail->last_activity_at)) : '-');
            $row++;
        }

        $row += 2;

        // SECTION 2: BREAKDOWN PER HARI
        $sheet->setCellValue('A' . $row, 'BREAKDOWN PER HARI');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFF3E0');
        $row++;

        $sheet->setCellValue('A' . $row, 'Tanggal');
        $sheet->setCellValue('B' . $row, 'Hari');
        $sheet->setCellValue('C' . $row, 'Jenis Aktivitas');
        $sheet->setCellValue('D' . $row, 'Jumlah');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($dailyBreakdown as $daily) {
            $sheet->setCellValue('A' . $row, date('d M Y', strtotime($daily->tanggal)));
            $sheet->setCellValue('B' . $row, $daily->hari);
            $sheet->setCellValue('C' . $row, $daily->kategori_aktivitas);
            $sheet->setCellValue('D' . $row, $daily->total);
            $row++;
        }

        $row += 2;

        // SECTION 3: BREAKDOWN PER MINGGU
        $sheet->setCellValue('A' . $row, 'BREAKDOWN PER MINGGU');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E8F5E9');
        $row++;

        $sheet->setCellValue('A' . $row, 'Minggu Ke');
        $sheet->setCellValue('B' . $row, 'Rentang Tanggal');
        $sheet->setCellValue('C' . $row, 'Jenis Aktivitas');
        $sheet->setCellValue('D' . $row, 'Jumlah');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($weeklyBreakdown as $weekly) {
            $sheet->setCellValue('A' . $row, 'Minggu ' . $weekly->minggu_ke);
            $sheet->setCellValue('B' . $row, $weekly->rentang_tanggal);
            $sheet->setCellValue('C' . $row, $weekly->kategori_aktivitas);
            $sheet->setCellValue('D' . $row, $weekly->total);
            $row++;
        }

        $row += 2;

        // SECTION 4: BREAKDOWN PER BULAN
        $sheet->setCellValue('A' . $row, 'BREAKDOWN PER BULAN');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FCE4EC');
        $row++;

        $sheet->setCellValue('A' . $row, 'Bulan');
        $sheet->setCellValue('B' . $row, 'Jenis Aktivitas');
        $sheet->setCellValue('C' . $row, 'Jumlah');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($monthlyBreakdown as $monthly) {
            $sheet->setCellValue('A' . $row, $monthly->bulan);
            $sheet->setCellValue('B' . $row, $monthly->kategori_aktivitas);
            $sheet->setCellValue('C' . $row, $monthly->total);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Aktivitas_' . $pegawai->nip . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Export detail aktivitas pegawai ke PDF dengan breakdown per hari/minggu/bulan
     */
    public function exportPegawaiPdf(Request $request, $nip)
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

        // Get aktivitas breakdown
        $dailyBreakdown = $this->getPegawaiDailyBreakdown($nip, $dateFrom, $dateTo);
        $weeklyBreakdown = $this->getPegawaiWeeklyBreakdown($nip, $dateFrom, $dateTo);
        $monthlyBreakdown = $this->getPegawaiMonthlyBreakdown($nip, $dateFrom, $dateTo);
        $detailAktivitas = $dateFrom || $dateTo
            ? $this->getDetailAktivitasFiltered($nip, $dateFrom, $dateTo)
            : PegawaiAktivitasSummary::where('nip', $nip)->orderByDesc('total_aktivitas')->get();

        $totalAktivitas = $detailAktivitas->sum('total_aktivitas');

        // Prepare period text
        $periodText = '';
        if ($dateFrom && $dateTo) {
            $periodText = date('d M Y', strtotime($dateFrom)) . ' - ' . date('d M Y', strtotime($dateTo));
        } elseif ($dateFrom) {
            $periodText = 'Dari ' . date('d M Y', strtotime($dateFrom));
        } elseif ($dateTo) {
            $periodText = 'Sampai ' . date('d M Y', strtotime($dateTo));
        } else {
            $periodText = 'Semua Periode';
        }

        // Load PDF
        $pdf = \PDF::loadView('statistik.detail-aktivitas-pegawai-pdf', compact(
            'pegawai',
            'detailAktivitas',
            'dailyBreakdown',
            'weeklyBreakdown',
            'monthlyBreakdown',
            'totalAktivitas',
            'periodText',
            'dateFrom',
            'dateTo'
        ));

        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        $filename = 'Aktivitas_' . $pegawai->nip . '_' . date('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Helper: Get daily breakdown of activities for specific pegawai
     */
    private function getPegawaiDailyBreakdown($nip, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('log_aktivitas')
            ->selectRaw('
                DATE(created_at_log) as tanggal,
                day_name as hari,
                ' . $this->getCategoryCase() . ' as kategori_aktivitas,
                COUNT(*) as total
            ')
            ->where('created_by_nip', $nip);

        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        return $query->groupBy('tanggal', 'hari', 'kategori_aktivitas')
            ->orderBy('tanggal')
            ->orderBy('kategori_aktivitas')
            ->get();
    }

    /**
     * Helper: Get weekly breakdown of activities for specific pegawai
     */
    private function getPegawaiWeeklyBreakdown($nip, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('log_aktivitas')
            ->selectRaw('
                YEAR(created_at_log) as tahun,
                WEEK(created_at_log, 1) as minggu_ke,
                DATE(MIN(created_at_log)) as tanggal_mulai,
                DATE(MAX(created_at_log)) as tanggal_akhir,
                ' . $this->getCategoryCase() . ' as kategori_aktivitas,
                COUNT(*) as total
            ')
            ->where('created_by_nip', $nip);

        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $results = $query->groupBy('tahun', 'minggu_ke', 'kategori_aktivitas')
            ->orderBy('tahun')
            ->orderBy('minggu_ke')
            ->orderBy('kategori_aktivitas')
            ->get();

        // Add formatted date range
        foreach ($results as $result) {
            $result->rentang_tanggal = date('d M', strtotime($result->tanggal_mulai)) . ' - ' . date('d M Y', strtotime($result->tanggal_akhir));
        }

        return $results;
    }

    /**
     * Helper: Get monthly breakdown of activities for specific pegawai
     */
    private function getPegawaiMonthlyBreakdown($nip, $dateFrom = null, $dateTo = null)
    {
        $query = DB::table('log_aktivitas')
            ->selectRaw('
                DATE_FORMAT(created_at_log, "%Y-%m") as bulan_value,
                DATE_FORMAT(created_at_log, "%M %Y") as bulan,
                ' . $this->getCategoryCase() . ' as kategori_aktivitas,
                COUNT(*) as total
            ')
            ->where('created_by_nip', $nip);

        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        return $query->groupBy('bulan_value', 'bulan', 'kategori_aktivitas')
            ->orderBy('bulan_value')
            ->orderBy('kategori_aktivitas')
            ->get();
    }

    /**
     * Export detail aktivitas pegawai with processed NIP and Instansi
     */
    public function exportDetailExcel(Request $request, $nip)
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
            ];
        }

        // Get aktivitas summary per kategori
        $query = DB::table('log_aktivitas')
            ->selectRaw($this->getCategoryCase() . ' as kategori_aktivitas, COUNT(*) as total_aktivitas')
            ->where('created_by_nip', $nip);

        if ($dateFrom) {
            $query->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $query->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $aktivitasSummary = $query->groupBy('kategori_aktivitas')
            ->orderByDesc('total_aktivitas')
            ->get();

        // Get list NIP dan instansi yang diproses
        // Extract NIP from details column (format: "... PNS NAME (NIP) ...")
        $subquery = DB::table('log_aktivitas')
            ->selectRaw('
                CASE
                    WHEN details LIKE "%(%)"
                    THEN SUBSTRING_INDEX(SUBSTRING_INDEX(details, "(", -1), ")", 1)
                    ELSE NULL
                END as extracted_nip,
                event_name,
                inject_type,
                details,
                created_at_log
            ')
            ->where('created_by_nip', $nip)
            ->whereNotNull('details');

        if ($dateFrom) {
            $subquery->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $subquery->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $processedData = DB::table(DB::raw('(' . $subquery->toSql() . ') as extracted'))
            ->mergeBindings($subquery)
            ->join('dms_pns as dp', 'extracted.extracted_nip', '=', 'dp.nip')
            ->select(
                'dp.nip',
                'dp.nama',
                'dp.instansi_nama',
                DB::raw('CASE
                    WHEN extracted.inject_type = "mapping"
                        THEN "Mapping Dokumen"
                    WHEN extracted.inject_type = "unggah"
                        THEN "Inject Dokumen"
                    WHEN extracted.event_name = "unggah_dokumen"
                        THEN "Unggah Dokumen"
                    WHEN extracted.event_name = "mapping_dokumen" AND extracted.inject_type IS NULL
                        THEN "Mapping Dokumen"
                    ELSE "Lainnya"
                END as kategori_aktivitas'),
                DB::raw('COUNT(*) as jumlah_aktivitas')
            )
            ->groupBy('dp.nip', 'dp.nama', 'dp.instansi_nama', 'kategori_aktivitas')
            ->orderBy('dp.instansi_nama')
            ->orderBy('dp.nama')
            ->get();

        // Get count of unidentified activities
        $unidentifiedCount = DB::table('log_aktivitas')
            ->where('created_by_nip', $nip)
            ->where(function($q) {
                $q->whereNull('details')
                  ->orWhereRaw('details NOT LIKE "%(%)"');
            });

        if ($dateFrom) {
            $unidentifiedCount->where('created_at_log', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo) {
            $unidentifiedCount->where('created_at_log', '<=', $dateTo . ' 23:59:59');
        }

        $unidentifiedCount = $unidentifiedCount->count();

        // Create Excel
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // Sheet 1: Ringkasan Aktivitas
        $this->createRingkasanSheet($spreadsheet, $pegawai, $aktivitasSummary, $dateFrom, $dateTo, $unidentifiedCount);

        // Sheet 2: Detail NIP yang Diproses
        $this->createDetailNIPSheet($spreadsheet, $processedData, $unidentifiedCount);

        // Set active sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Save and download
        $filename = 'Detail_Aktivitas_' . $nip . '_' . date('YmdHis') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Create ringkasan aktivitas sheet
     */
    private function createRingkasanSheet($spreadsheet, $pegawai, $aktivitasSummary, $dateFrom, $dateTo, $unidentifiedCount = 0)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ringkasan Aktivitas');

        $row = 1;

        // Header Info
        $sheet->setCellValue('A' . $row, 'DETAIL AKTIVITAS PEGAWAI');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        // Pegawai Info
        $sheet->setCellValue('A' . $row, 'NIP');
        $sheet->setCellValue('B' . $row, $pegawai->nip);
        $row++;
        $sheet->setCellValue('A' . $row, 'Nama');
        $sheet->setCellValue('B' . $row, $pegawai->nama);
        $row++;

        // Period
        if ($dateFrom || $dateTo) {
            $sheet->setCellValue('A' . $row, 'Periode');
            $periodText = ($dateFrom ? date('d/m/Y', strtotime($dateFrom)) : 'Awal') . ' - ' .
                         ($dateTo ? date('d/m/Y', strtotime($dateTo)) : 'Akhir');
            $sheet->setCellValue('B' . $row, $periodText);
            $row++;
        }

        $row += 2;

        // Table Header
        $sheet->setCellValue('A' . $row, 'No');
        $sheet->setCellValue('B' . $row, 'Jenis Aktivitas');
        $sheet->setCellValue('C' . $row, 'Jumlah');
        $sheet->setCellValue('D' . $row, 'Persentase');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle);
        $row++;

        // Data
        $no = 1;
        $total = $aktivitasSummary->sum('total_aktivitas');

        foreach ($aktivitasSummary as $item) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $item->kategori_aktivitas);
            $sheet->setCellValue('C' . $row, number_format($item->total_aktivitas));
            $percentage = $total > 0 ? ($item->total_aktivitas / $total * 100) : 0;
            $sheet->setCellValue('D' . $row, number_format($percentage, 1) . '%');
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, '');
        $sheet->setCellValue('B' . $row, 'TOTAL');
        $sheet->setCellValue('C' . $row, number_format($total));
        $sheet->setCellValue('D' . $row, '100%');
        $sheet->getStyle('B' . $row . ':D' . $row)->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Create detail NIP yang diproses sheet
     */
    private function createDetailNIPSheet($spreadsheet, $processedData, $unidentifiedCount = 0)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Detail NIP Diproses');

        $row = 1;

        // Header
        $sheet->setCellValue('A' . $row, 'DETAIL NIP DAN INSTANSI YANG DIPROSES');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        // Table Header
        $sheet->setCellValue('A' . $row, 'No');
        $sheet->setCellValue('B' . $row, 'NIP');
        $sheet->setCellValue('C' . $row, 'Nama PNS');
        $sheet->setCellValue('D' . $row, 'Instansi');
        $sheet->setCellValue('E' . $row, 'Jenis Aktivitas');
        $sheet->setCellValue('F' . $row, 'Jumlah');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($headerStyle);
        $row++;

        // Data
        $no = 1;
        $totalAktivitas = 0;

        if ($processedData->count() > 0) {
            foreach ($processedData as $item) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $item->nip);
                $sheet->setCellValue('C' . $row, $item->nama);
                $sheet->setCellValue('D' . $row, $item->instansi_nama);
                $sheet->setCellValue('E' . $row, $item->kategori_aktivitas);
                $sheet->setCellValue('F' . $row, number_format($item->jumlah_aktivitas));
                $totalAktivitas += $item->jumlah_aktivitas;
                $row++;
            }

            // Total row
            $sheet->setCellValue('A' . $row, '');
            $sheet->setCellValue('B' . $row, '');
            $sheet->setCellValue('C' . $row, '');
            $sheet->setCellValue('D' . $row, '');
            $sheet->setCellValue('E' . $row, 'TOTAL');
            $sheet->setCellValue('F' . $row, number_format($totalAktivitas));
            $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
        } else {
            $sheet->setCellValue('A' . $row, 'Tidak ada data NIP yang diproses');
            $sheet->mergeCells('A' . $row . ':F' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Show efektivitas kerja page
     */
    public function efektivitasKerja()
    {
        return view('statistik.efektivitas-kerja');
    }

    /**
     * Get efektivitas kerja data (mapping non-inject / jam kerja ASN)
     * Jam kerja: Senin-Kamis = 7.5 jam, Jumat = 7.5 jam
     */
    public function getEfektivitasKerja(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            return response()->json(['error' => 'Tanggal mulai dan akhir harus diisi'], 400);
        }

        // Hitung total hari kerja (exclude Sabtu & Minggu)
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5; // 7.5 jam per hari

        // OPTIMIZED: Get both total mapping dan per pegawai dalam single query
        // Join ke dms_pns untuk mendapatkan instansi_nama
        $results = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COALESCE(dp.instansi_nama, "Tidak Diketahui") as instansi_nama'),
                DB::raw('COUNT(*) as total_mapping')
            )
            ->where('la.event_name', 'mapping_dokumen')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'dp.instansi_nama', 'la.created_by_nama')
            ->orderByDesc('total_mapping')
            ->get();

        // Calculate total dari sum (tanpa query tambahan)
        $totalMapping = $results->sum('total_mapping');
        $totalPegawai = $results->count(); // Jumlah pegawai yang ada data

        // Map efektivitas per pegawai
        $efektivitasPerPegawai = $results->map(function ($item) use ($totalWorkingHours) {
            $item->efektivitas = $totalWorkingHours > 0
                ? round($item->total_mapping / $totalWorkingHours, 2)
                : 0;
            return $item;
        });

        // Calculate overall efektivitas
        $efektivitasTotal = $totalWorkingHours > 0
            ? round($totalMapping / $totalWorkingHours, 2)
            : 0;

        // METRIK TAMBAHAN
        // 1. Rata-rata dokumen per orang
        $avgPerPerson = $totalPegawai > 0
            ? round($totalMapping / $totalPegawai, 2)
            : 0;

        // 2. Berapa menit per 1 dokumen (waktu yang dibutuhkan untuk 1 dokumen)
        $totalWorkingMinutes = $totalWorkingHours * 60;
        $minutesPerDoc = $totalMapping > 0
            ? round($totalWorkingMinutes / $totalMapping, 2)
            : 0;

        // 3. Berapa dokumen per menit (produktivitas per menit)
        $docsPerMinute = $totalWorkingMinutes > 0
            ? round($totalMapping / $totalWorkingMinutes, 4)
            : 0;

        return response()->json([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_working_days' => $totalWorkingDays,
            'total_working_hours' => $totalWorkingHours,
            'total_working_minutes' => $totalWorkingMinutes,
            'total_mapping' => $totalMapping,
            'total_pegawai' => $totalPegawai,
            'efektivitas_total' => $efektivitasTotal,
            'avg_per_person' => $avgPerPerson,
            'minutes_per_doc' => $minutesPerDoc,
            'docs_per_minute' => $docsPerMinute,
            'efektivitas_per_pegawai' => $efektivitasPerPegawai,
        ]);
    }

    /**
     * Calculate working days (exclude weekends)
     */
    private function calculateWorkingDays($dateFrom, $dateTo)
    {
        $start = new \DateTime($dateFrom);
        $end = new \DateTime($dateTo);
        $end->modify('+1 day'); // Include end date

        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end);

        $workingDays = 0;
        foreach ($dateRange as $date) {
            $dayOfWeek = $date->format('N'); // 1 (Mon) to 7 (Sun)
            if ($dayOfWeek < 6) { // Monday to Friday
                $workingDays++;
            }
        }

        return $workingDays;
    }

    /**
     * Export efektivitas kerja to Excel
     */
    public function exportEfektivitasExcel(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $groupBy = $request->get('group_by', 'daily'); // daily, weekly, monthly

        if (!$dateFrom || !$dateTo) {
            return back()->with('error', 'Tanggal mulai dan akhir harus diisi');
        }

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Ringkasan Total
        $this->createEfektivitasSummarySheet($spreadsheet, $dateFrom, $dateTo);

        // Sheet 2: List Pegawai (Ranking)
        $this->createEfektivitasPerPegawaiSheet($spreadsheet, $dateFrom, $dateTo);

        // Sheet 3: Per Minggu (7 hari)
        $this->createEfektivitasPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, 'weekly');

        // Sheet 4: Per Hari
        $this->createEfektivitasPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, 'daily');

        $filename = 'Efektivitas_Kerja_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export efektivitas kerja to PDF
     */
    public function exportEfektivitasPdf(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $groupBy = $request->get('group_by', 'daily');

        if (!$dateFrom || !$dateTo) {
            return response()->json(['error' => 'Tanggal mulai dan akhir harus diisi'], 400);
        }

        // Get data
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        // Get total mapping (semua pegawai)
        $totalMapping = DB::table('log_aktivitas')
            ->where('event_name', 'mapping_dokumen')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $efektivitasTotal = $totalWorkingHours > 0
            ? round($totalMapping / $totalWorkingHours, 2)
            : 0;

        // Hitung jumlah pegawai unik
        $totalPegawai = DB::table('log_aktivitas')
            ->where('event_name', 'mapping_dokumen')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->distinct('created_by_nip')
            ->count('created_by_nip');

        // METRIK TAMBAHAN
        $totalWorkingMinutes = $totalWorkingHours * 60;
        $avgPerPerson = $totalPegawai > 0 ? round($totalMapping / $totalPegawai, 2) : 0;
        $minutesPerDoc = $totalMapping > 0 ? round($totalWorkingMinutes / $totalMapping, 2) : 0;
        $docsPerMinute = $totalWorkingMinutes > 0 ? round($totalMapping / $totalWorkingMinutes, 4) : 0;

        // Get per pegawai data (ranking)
        $efektivitasPerPegawai = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_mapping')
            )
            ->where('la.event_name', 'mapping_dokumen')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'la.created_by_nama')
            ->orderByDesc('total_mapping')
            ->get()
            ->map(function ($item) use ($totalWorkingHours) {
                return [
                    'nip' => $item->nip,
                    'nama' => $item->nama,
                    'total_mapping' => $item->total_mapping,
                    'efektivitas' => $totalWorkingHours > 0 ? round($item->total_mapping / $totalWorkingHours, 2) : 0,
                ];
            })
            ->toArray();

        // Get per periode data (per minggu dan per hari)
        $efektivitasPerMinggu = $this->getEfektivitasPerPeriode($dateFrom, $dateTo, 'weekly');
        $efektivitasPerHari = $this->getEfektivitasPerPeriode($dateFrom, $dateTo, 'daily');

        $data = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_working_days' => $totalWorkingDays,
            'total_working_hours' => $totalWorkingHours,
            'total_working_minutes' => $totalWorkingMinutes,
            'total_mapping' => $totalMapping,
            'total_pegawai' => $totalPegawai,
            'efektivitas_total' => $efektivitasTotal,
            'avg_per_person' => $avgPerPerson,
            'minutes_per_doc' => $minutesPerDoc,
            'docs_per_minute' => $docsPerMinute,
            'efektivitas_per_pegawai' => $efektivitasPerPegawai,
            'efektivitas_per_minggu' => $efektivitasPerMinggu,
            'efektivitas_per_hari' => $efektivitasPerHari,
        ];

        $pdf = \PDF::loadView('pdf.efektivitas-kerja', $data);
        $pdf->setPaper('a4', 'landscape');

        $filename = 'Efektivitas_Kerja_' . date('Ymd_His') . '.pdf';
        return $pdf->stream($filename);
    }

    private function createEfektivitasSummarySheet($spreadsheet, $dateFrom, $dateTo)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Total');

        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $totalMapping = DB::table('log_aktivitas')
            ->where('event_name', 'mapping_dokumen')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $efektivitasTotal = $totalWorkingHours > 0
            ? round($totalMapping / $totalWorkingHours, 2)
            : 0;

        $row = 1;
        $sheet->setCellValue('A' . $row, 'LAPORAN EFEKTIVITAS KERJA MAPPING NON-INJECT');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Periode');
        $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Hari Kerja');
        $sheet->setCellValue('B' . $row, $totalWorkingDays . ' hari');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Jam Kerja');
        $sheet->setCellValue('B' . $row, $totalWorkingHours . ' jam (7.5 jam/hari)');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Mapping Non-Inject');
        $sheet->setCellValue('B' . $row, number_format($totalMapping, 0, ',', '.') . ' dokumen');
        $row++;

        $sheet->setCellValue('A' . $row, 'Efektivitas Total');
        $sheet->setCellValue('B' . $row, $efektivitasTotal . ' dok/jam');
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FFEB3B');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    private function createEfektivitasPerPegawaiSheet($spreadsheet, $dateFrom, $dateTo)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ranking Pegawai');

        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $efektivitasPerPegawai = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COALESCE(dp.instansi_nama, "Tidak Diketahui") as instansi_nama'),
                DB::raw('COUNT(*) as total_mapping')
            )
            ->where('la.event_name', 'mapping_dokumen')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'dp.instansi_nama', 'la.created_by_nama')
            ->orderByDesc('total_mapping')
            ->get()
            ->map(function ($item) use ($totalWorkingHours) {
                $item->efektivitas = $totalWorkingHours > 0
                    ? round($item->total_mapping / $totalWorkingHours, 2)
                    : 0;
                return $item;
            });

        $row = 1;
        $sheet->setCellValue('A' . $row, 'No');
        $sheet->setCellValue('B' . $row, 'NIP');
        $sheet->setCellValue('C' . $row, 'Nama');
        $sheet->setCellValue('D' . $row, 'Total Mapping');
        $sheet->setCellValue('E' . $row, 'Efektivitas (dok/jam)');

        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':E' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4CAF50');

        $row++;
        $no = 1;
        foreach ($efektivitasPerPegawai as $item) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $item->nip);
            $sheet->setCellValue('C' . $row, $item->nama);
            $sheet->setCellValue('D' . $row, $item->total_mapping);
            $sheet->setCellValue('E' . $row, $item->efektivitas);

            // Format numbers in Excel (not as string)
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function createEfektivitasPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, $groupBy)
    {
        $sheet = $spreadsheet->createSheet();

        // Set title based on groupBy
        if ($groupBy === 'daily') {
            $sheet->setTitle('Per Hari');
        } elseif ($groupBy === 'weekly') {
            $sheet->setTitle('Per Minggu');
        } else {
            $sheet->setTitle('Per Bulan');
        }

        $efektivitasPerPeriode = $this->getEfektivitasPerPeriode($dateFrom, $dateTo, $groupBy);

        $row = 1;
        $sheet->setCellValue('A' . $row, 'Periode');
        $sheet->setCellValue('B' . $row, 'Hari Kerja');
        $sheet->setCellValue('C' . $row, 'Jam Kerja');
        $sheet->setCellValue('D' . $row, 'Total Mapping');
        $sheet->setCellValue('E' . $row, 'Efektivitas (dok/jam)');

        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':E' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2196F3');

        $row++;
        foreach ($efektivitasPerPeriode as $item) {
            $sheet->setCellValue('A' . $row, $item['periode']);
            $sheet->setCellValue('B' . $row, $item['working_days']);
            $sheet->setCellValue('C' . $row, $item['working_hours']);
            $sheet->setCellValue('D' . $row, $item['total_mapping']);
            $sheet->setCellValue('E' . $row, $item['efektivitas']);

            // Format numbers in Excel (not as string)
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.0');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function getEfektivitasPerPeriode($dateFrom, $dateTo, $groupBy)
    {
        $result = [];

        if ($groupBy === 'daily') {
            $start = new \DateTime($dateFrom);
            $end = new \DateTime($dateTo);
            $end->modify('+1 day');

            $interval = new \DateInterval('P1D');
            $dateRange = new \DatePeriod($start, $interval, $end);

            foreach ($dateRange as $date) {
                $dayOfWeek = $date->format('N');
                if ($dayOfWeek >= 6) continue; // Skip weekends

                $dateStr = $date->format('Y-m-d');
                $workingHours = 7.5;

                // Hitung total mapping
                $totalMapping = DB::table('log_aktivitas')
                    ->where('event_name', 'mapping_dokumen')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [$dateStr . ' 00:00:00', $dateStr . ' 23:59:59'])
                    ->count();

                // Ambil day_name dari database (ambil 1 record saja untuk menghemat proses)
                $dayNameRecord = DB::table('log_aktivitas')
                    ->where('event_name', 'mapping_dokumen')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [$dateStr . ' 00:00:00', $dateStr . ' 23:59:59'])
                    ->whereNotNull('day_name')
                    ->value('day_name');

                $dayName = $dayNameRecord ?? $this->getDayNameIndo($dayOfWeek);

                $result[] = [
                    'periode' => $date->format('d/m/Y') . ' (' . $dayName . ')',
                    'working_days' => 1,
                    'working_hours' => $workingHours,
                    'total_mapping' => $totalMapping,
                    'efektivitas' => round($totalMapping / $workingHours, 2),
                ];
            }
        } elseif ($groupBy === 'weekly') {
            $start = new \DateTime($dateFrom);
            $end = new \DateTime($dateTo);

            // Mulai dari tanggal yang dipilih user (tidak mundur ke Senin)
            $weekStart = clone $start;

            while ($weekStart <= $end) {
                $weekEnd = clone $weekStart;
                $weekEnd->modify('+6 days');

                // Jangan melewati tanggal akhir yang dipilih user
                if ($weekEnd > $end) {
                    $weekEnd = clone $end;
                }

                $workingDays = $this->calculateWorkingDays($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
                $workingHours = $workingDays * 7.5;

                $totalMapping = DB::table('log_aktivitas')
                    ->where('event_name', 'mapping_dokumen')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [
                        $weekStart->format('Y-m-d') . ' 00:00:00',
                        $weekEnd->format('Y-m-d') . ' 23:59:59'
                    ])
                    ->count();

                $result[] = [
                    'periode' => 'Minggu ' . $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y'),
                    'working_days' => $workingDays,
                    'working_hours' => $workingHours,
                    'total_mapping' => $totalMapping,
                    'efektivitas' => $workingHours > 0 ? round($totalMapping / $workingHours, 2) : 0,
                ];

                $weekStart->modify('+7 days');
            }
        } elseif ($groupBy === 'monthly') {
            $start = new \DateTime($dateFrom);
            $end = new \DateTime($dateTo);

            $monthStart = clone $start;
            $monthStart->modify('first day of this month');

            while ($monthStart <= $end) {
                $monthEnd = clone $monthStart;
                $monthEnd->modify('last day of this month');

                if ($monthEnd > $end) {
                    $monthEnd = clone $end;
                }

                $workingDays = $this->calculateWorkingDays($monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'));
                $workingHours = $workingDays * 7.5;

                $totalMapping = DB::table('log_aktivitas')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [
                        $monthStart->format('Y-m-d') . ' 00:00:00',
                        $monthEnd->format('Y-m-d') . ' 23:59:59'
                    ])
                    ->count();

                $result[] = [
                    'periode' => $monthStart->format('F Y'),
                    'working_days' => $workingDays,
                    'working_hours' => $workingHours,
                    'total_mapping' => $totalMapping,
                    'efektivitas' => $workingHours > 0 ? round($totalMapping / $workingHours, 2) : 0,
                ];

                $monthStart->modify('first day of next month');
            }
        }

        return $result;
    }

    /**
     * Get Indonesian day name from day number (1-7)
     */
    private function getDayNameIndo($dayNumber)
    {
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu'
        ];

        return $days[$dayNumber] ?? 'Unknown';
    }

    /**
     * ========================================
     * EFEKTIVITAS APPROVAL DOKUMEN MYASN
     * ========================================
     */

    /**
     * Show Efektivitas Approval page
     */
    public function efektivitasApproval()
    {
        return view('statistik.efektivitas-approval');
    }

    /**
     * Get Efektivitas Approval data (AJAX)
     */
    public function getEfektivitasApproval(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            return response()->json(['error' => 'Tanggal mulai dan akhir harus diisi'], 400);
        }

        // Hitung total hari kerja dan jam kerja
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        // Query data approval per pegawai dengan JOIN ke dms_pns
        $results = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_approval')
            )
            ->where('la.event_name', 'approve_upload_dok_myasn')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'la.created_by_nama')
            ->orderByDesc('total_approval')
            ->get();

        $totalApproval = $results->sum('total_approval');
        $totalPegawai = $results->count();

        // Hitung efektivitas per pegawai
        $efektivitasPerPegawai = $results->map(function ($item) use ($totalWorkingHours) {
            return [
                'nip' => $item->nip,
                'nama' => $item->nama,
                'total_approval' => $item->total_approval,
                'efektivitas' => $totalWorkingHours > 0 ? number_format($item->total_approval / $totalWorkingHours, 2) : '0.00',
            ];
        });

        // Hitung efektivitas total
        $efektivitasTotal = $totalWorkingHours > 0 ? number_format($totalApproval / $totalWorkingHours, 2) : '0.00';

        // METRIK TAMBAHAN
        $totalWorkingMinutes = $totalWorkingHours * 60;
        $avgPerPerson = $totalPegawai > 0 ? round($totalApproval / $totalPegawai, 2) : 0;
        $minutesPerApproval = $totalApproval > 0 ? round($totalWorkingMinutes / $totalApproval, 2) : 0;
        $approvalsPerMinute = $totalWorkingMinutes > 0 ? round($totalApproval / $totalWorkingMinutes, 4) : 0;

        return response()->json([
            'total_working_days' => $totalWorkingDays,
            'total_working_hours' => $totalWorkingHours,
            'total_working_minutes' => $totalWorkingMinutes,
            'total_approval' => $totalApproval,
            'total_pegawai' => $totalPegawai,
            'efektivitas_total' => $efektivitasTotal,
            'avg_per_person' => $avgPerPerson,
            'minutes_per_approval' => $minutesPerApproval,
            'approvals_per_minute' => $approvalsPerMinute,
            'efektivitas_per_pegawai' => $efektivitasPerPegawai,
        ]);
    }

    /**
     * Export Efektivitas Approval to Excel
     */
    public function exportEfektivitasApprovalExcel(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            return back()->with('error', 'Tanggal mulai dan akhir harus diisi');
        }

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Ringkasan Total
        $this->createEfektivitasApprovalSummarySheet($spreadsheet, $dateFrom, $dateTo);

        // Sheet 2: Ranking Pegawai
        $this->createEfektivitasApprovalPerPegawaiSheet($spreadsheet, $dateFrom, $dateTo);

        // Sheet 3: Per Minggu (7 hari)
        $this->createEfektivitasApprovalPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, 'weekly');

        // Sheet 4: Per Hari
        $this->createEfektivitasApprovalPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, 'daily');

        // Set sheet 1 sebagai active sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Generate filename
        $filename = 'Efektivitas_Approval_' . date('Y-m-d', strtotime($dateFrom)) . '_sd_' . date('Y-m-d', strtotime($dateTo)) . '.xlsx';

        // Download
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Create Excel Summary Sheet for Approval
     */
    private function createEfektivitasApprovalSummarySheet($spreadsheet, $dateFrom, $dateTo)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Total');

        // Header
        $sheet->setCellValue('A1', 'RINGKASAN EFEKTIVITAS APPROVAL DOKUMEN MYASN');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Periode
        $sheet->setCellValue('A2', 'Periode:');
        $sheet->setCellValue('B2', date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet->getStyle('A2')->getFont()->setBold(true);

        // Data
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $totalApproval = DB::table('log_aktivitas')
            ->where('event_name', 'approve_upload_dok_myasn')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $efektivitasTotal = $totalWorkingHours > 0 ? round($totalApproval / $totalWorkingHours, 2) : 0;

        $sheet->setCellValue('A4', 'Total Hari Kerja (Senin-Jumat)');
        $sheet->setCellValue('B4', $totalWorkingDays);
        $sheet->setCellValue('A5', 'Total Jam Kerja (7.5 jam/hari)');
        $sheet->setCellValue('B5', $totalWorkingHours);
        $sheet->setCellValue('A6', 'Total Approval Dokumen MyASN');
        $sheet->setCellValue('B6', $totalApproval);
        $sheet->setCellValue('A7', 'Efektivitas Total (dokumen/jam)');
        $sheet->setCellValue('B7', $efektivitasTotal);

        // Styling
        $sheet->getStyle('A4:A7')->getFont()->setBold(true);
        $sheet->getStyle('A7:B7')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFE599');
        $sheet->getStyle('A7:B7')->getFont()->setBold(true);

        // Number format
        $sheet->getStyle('B4:B6')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0.0');
        $sheet->getStyle('B7')->getNumberFormat()->setFormatCode('#,##0.00');

        // Column width
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    /**
     * Create Excel Per Pegawai Sheet for Approval
     */
    private function createEfektivitasApprovalPerPegawaiSheet($spreadsheet, $dateFrom, $dateTo)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ranking Pegawai');

        // Header
        $sheet->setCellValue('A1', 'RANKING PEGAWAI - EFEKTIVITAS APPROVAL DOKUMEN MYASN');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Periode
        $sheet->setCellValue('A2', 'Periode:');
        $sheet->setCellValue('B2', date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet->mergeCells('B2:E2');

        // Table header
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'NIP');
        $sheet->setCellValue('C4', 'Nama');
        $sheet->setCellValue('D4', 'Total Approval');
        $sheet->setCellValue('E4', 'Efektivitas (dok/jam)');

        $sheet->getStyle('A4:E4')->getFont()->setBold(true);
        $sheet->getStyle('A4:E4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4CAF50');
        $sheet->getStyle('A4:E4')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A4:E4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Get data
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $results = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_approval')
            )
            ->where('la.event_name', 'approve_upload_dok_myasn')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'la.created_by_nama')
            ->orderByDesc('total_approval')
            ->get();

        // Fill data
        $row = 5;
        $no = 1;
        foreach ($results as $item) {
            $efektivitas = $totalWorkingHours > 0 ? round($item->total_approval / $totalWorkingHours, 2) : 0;

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $item->nip);
            $sheet->setCellValue('C' . $row, $item->nama);
            $sheet->setCellValue('D' . $row, $item->total_approval);
            $sheet->setCellValue('E' . $row, $efektivitas);

            // Format
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $row++;
        }

        // Column width
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(22);

        // Borders
        if ($row > 5) {
            $sheet->getStyle('A4:E' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }
    }

    /**
     * Create Excel Per Periode Sheet for Approval
     */
    private function createEfektivitasApprovalPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, $groupBy)
    {
        $sheet = $spreadsheet->createSheet();
        $title = $groupBy === 'weekly' ? 'Per Minggu (7 Hari)' : 'Per Hari';
        $sheet->setTitle($title);

        // Header
        $headerText = $groupBy === 'weekly' ? 'EFEKTIVITAS APPROVAL PER MINGGU (7 HARI)' : 'EFEKTIVITAS APPROVAL PER HARI';
        $sheet->setCellValue('A1', $headerText);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Periode
        $sheet->setCellValue('A2', 'Periode:');
        $sheet->setCellValue('B2', date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet->mergeCells('B2:E2');

        // Table header
        $sheet->setCellValue('A4', 'Periode');
        $sheet->setCellValue('B4', 'Hari Kerja');
        $sheet->setCellValue('C4', 'Jam Kerja');
        $sheet->setCellValue('D4', 'Total Approval');
        $sheet->setCellValue('E4', 'Efektivitas (dok/jam)');

        $sheet->getStyle('A4:E4')->getFont()->setBold(true);
        $sheet->getStyle('A4:E4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2196F3');
        $sheet->getStyle('A4:E4')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A4:E4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Get data
        $efektivitasPerPeriode = $this->getEfektivitasApprovalPerPeriode($dateFrom, $dateTo, $groupBy);

        // Fill data
        $row = 5;
        foreach ($efektivitasPerPeriode as $item) {
            $sheet->setCellValue('A' . $row, $item['periode']);
            $sheet->setCellValue('B' . $row, $item['working_days']);
            $sheet->setCellValue('C' . $row, $item['working_hours']);
            $sheet->setCellValue('D' . $row, $item['total_approval']);
            $sheet->setCellValue('E' . $row, $item['efektivitas']);

            // Format
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.0');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $row++;
        }

        // Column width
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(22);

        // Borders
        if ($row > 5) {
            $sheet->getStyle('A4:E' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }
    }

    /**
     * Get Efektivitas Approval Per Periode (weekly/daily)
     */
    private function getEfektivitasApprovalPerPeriode($dateFrom, $dateTo, $groupBy)
    {
        $result = [];

        if ($groupBy === 'daily') {
            $start = new \DateTime($dateFrom);
            $end = new \DateTime($dateTo);
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $dayOfWeek = (int)$date->format('N');

                // Skip weekend
                if ($dayOfWeek == 6 || $dayOfWeek == 7) {
                    continue;
                }

                $workingDays = 1;
                $workingHours = 7.5;

                // Hitung total approval
                $totalApproval = DB::table('log_aktivitas')
                    ->where('event_name', 'approve_upload_dok_myasn')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [$dateStr . ' 00:00:00', $dateStr . ' 23:59:59'])
                    ->count();

                // Ambil day_name dari database
                $dayNameRecord = DB::table('log_aktivitas')
                    ->where('event_name', 'approve_upload_dok_myasn')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [$dateStr . ' 00:00:00', $dateStr . ' 23:59:59'])
                    ->whereNotNull('day_name')
                    ->value('day_name');

                $dayName = $dayNameRecord ?? $this->getDayNameIndo($dayOfWeek);

                $result[] = [
                    'periode' => $dayName . ', ' . $date->format('d/m/Y'),
                    'working_days' => $workingDays,
                    'working_hours' => $workingHours,
                    'total_approval' => $totalApproval,
                    'efektivitas' => $workingHours > 0 ? round($totalApproval / $workingHours, 2) : 0,
                ];
            }
        } elseif ($groupBy === 'weekly') {
            $start = new \DateTime($dateFrom);
            $end = new \DateTime($dateTo);

            $weekStart = clone $start;

            while ($weekStart <= $end) {
                $weekEnd = clone $weekStart;
                $weekEnd->modify('+6 days');

                if ($weekEnd > $end) {
                    $weekEnd = clone $end;
                }

                $workingDays = $this->calculateWorkingDays($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
                $workingHours = $workingDays * 7.5;

                $totalApproval = DB::table('log_aktivitas')
                    ->where('event_name', 'approve_upload_dok_myasn')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [
                        $weekStart->format('Y-m-d') . ' 00:00:00',
                        $weekEnd->format('Y-m-d') . ' 23:59:59'
                    ])
                    ->count();

                $result[] = [
                    'periode' => 'Minggu ' . $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y'),
                    'working_days' => $workingDays,
                    'working_hours' => $workingHours,
                    'total_approval' => $totalApproval,
                    'efektivitas' => $workingHours > 0 ? round($totalApproval / $workingHours, 2) : 0,
                ];

                $weekStart->modify('+7 days');
            }
        }

        return $result;
    }

    /**
     * Export Efektivitas Approval to PDF
     */
    public function exportEfektivitasApprovalPdf(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            return back()->with('error', 'Tanggal mulai dan akhir harus diisi');
        }

        // Get data
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $totalApproval = DB::table('log_aktivitas')
            ->where('event_name', 'approve_upload_dok_myasn')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $efektivitasTotal = $totalWorkingHours > 0 ? number_format($totalApproval / $totalWorkingHours, 2) : '0.00';

        // Hitung jumlah pegawai unik
        $totalPegawai = DB::table('log_aktivitas')
            ->where('event_name', 'approve_upload_dok_myasn')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->distinct('created_by_nip')
            ->count('created_by_nip');

        // METRIK TAMBAHAN
        $totalWorkingMinutes = $totalWorkingHours * 60;
        $avgPerPerson = $totalPegawai > 0 ? round($totalApproval / $totalPegawai, 2) : 0;
        $minutesPerApproval = $totalApproval > 0 ? round($totalWorkingMinutes / $totalApproval, 2) : 0;
        $approvalsPerMinute = $totalWorkingMinutes > 0 ? round($totalApproval / $totalWorkingMinutes, 4) : 0;

        // Efektivitas per pegawai
        $results = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_approval')
            )
            ->where('la.event_name', 'approve_upload_dok_myasn')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'la.created_by_nama')
            ->orderByDesc('total_approval')
            ->get();

        $efektivitasPerPegawai = $results->map(function ($item) use ($totalWorkingHours) {
            return [
                'nip' => $item->nip,
                'nama' => $item->nama,
                'total_approval' => $item->total_approval,
                'efektivitas' => $totalWorkingHours > 0 ? number_format($item->total_approval / $totalWorkingHours, 2) : '0.00',
            ];
        })->toArray();

        // Efektivitas per minggu
        $efektivitasPerMinggu = $this->getEfektivitasApprovalPerPeriode($dateFrom, $dateTo, 'weekly');

        // Efektivitas per hari
        $efektivitasPerHari = $this->getEfektivitasApprovalPerPeriode($dateFrom, $dateTo, 'daily');

        // Prepare data for PDF with underscore variable names
        $date_from = $dateFrom;
        $date_to = $dateTo;
        $total_working_days = $totalWorkingDays;
        $total_working_hours = $totalWorkingHours;
        $total_working_minutes = $totalWorkingMinutes;
        $total_approval = $totalApproval;
        $total_pegawai = $totalPegawai;
        $efektivitas_total = $efektivitasTotal;
        $avg_per_person = $avgPerPerson;
        $minutes_per_approval = $minutesPerApproval;
        $approvals_per_minute = $approvalsPerMinute;
        $efektivitas_per_pegawai = $efektivitasPerPegawai;
        $efektivitas_per_minggu = $efektivitasPerMinggu;
        $efektivitas_per_hari = $efektivitasPerHari;

        // Generate PDF
        $pdf = PDF::loadView('pdf.efektivitas-approval', compact(
            'date_from',
            'date_to',
            'total_working_days',
            'total_working_hours',
            'total_working_minutes',
            'total_approval',
            'total_pegawai',
            'efektivitas_total',
            'avg_per_person',
            'minutes_per_approval',
            'approvals_per_minute',
            'efektivitas_per_pegawai',
            'efektivitas_per_minggu',
            'efektivitas_per_hari'
        ));

        $pdf->setPaper('A4', 'portrait');

        $filename = 'Efektivitas_Approval_' . date('Y-m-d', strtotime($dateFrom)) . '_sd_' . date('Y-m-d', strtotime($dateTo)) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * ========================================
     * EFEKTIVITAS LAPORAN KEKURANGAN BERKAS
     * ========================================
     */

    /**
     * Show Efektivitas Laporan Kekurangan page
     */
    public function efektivitasLaporanKekurangan()
    {
        return view('statistik.efektivitas-laporan-kekurangan');
    }

    /**
     * Get Efektivitas Laporan Kekurangan data (AJAX)
     */
    public function getEfektivitasLaporanKekurangan(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            return response()->json(['error' => 'Tanggal mulai dan akhir harus diisi'], 400);
        }

        // Hitung total hari kerja dan jam kerja
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        // Query data laporan kekurangan per pegawai dengan JOIN ke dms_pns
        $results = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_laporan')
            )
            ->where('la.event_name', 'Laporan-Kekurangan-Riwayat')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'la.created_by_nama')
            ->orderByDesc('total_laporan')
            ->get();

        $totalLaporan = $results->sum('total_laporan');
        $totalPegawai = $results->count();

        // Hitung efektivitas per pegawai
        $efektivitasPerPegawai = $results->map(function ($item) use ($totalWorkingHours) {
            return [
                'nip' => $item->nip,
                'nama' => $item->nama,
                'total_laporan' => $item->total_laporan,
                'efektivitas' => $totalWorkingHours > 0 ? number_format($item->total_laporan / $totalWorkingHours, 2) : '0.00',
            ];
        });

        // Hitung efektivitas total
        $efektivitasTotal = $totalWorkingHours > 0 ? number_format($totalLaporan / $totalWorkingHours, 2) : '0.00';

        // METRIK TAMBAHAN
        $totalWorkingMinutes = $totalWorkingHours * 60;
        $avgPerPerson = $totalPegawai > 0 ? round($totalLaporan / $totalPegawai, 2) : 0;
        $minutesPerLaporan = $totalLaporan > 0 ? round($totalWorkingMinutes / $totalLaporan, 2) : 0;
        $laporanPerMinute = $totalWorkingMinutes > 0 ? round($totalLaporan / $totalWorkingMinutes, 4) : 0;

        return response()->json([
            'total_working_days' => $totalWorkingDays,
            'total_working_hours' => $totalWorkingHours,
            'total_working_minutes' => $totalWorkingMinutes,
            'total_laporan' => $totalLaporan,
            'total_pegawai' => $totalPegawai,
            'efektivitas_total' => $efektivitasTotal,
            'avg_per_person' => $avgPerPerson,
            'minutes_per_laporan' => $minutesPerLaporan,
            'laporan_per_minute' => $laporanPerMinute,
            'efektivitas_per_pegawai' => $efektivitasPerPegawai,
        ]);
    }

    /**
     * Export Efektivitas Laporan Kekurangan to Excel
     */
    public function exportEfektivitasLaporanExcel(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            return back()->with('error', 'Tanggal mulai dan akhir harus diisi');
        }

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Ringkasan Total
        $this->createEfektivitasLaporanSummarySheet($spreadsheet, $dateFrom, $dateTo);

        // Sheet 2: Ranking Pegawai
        $this->createEfektivitasLaporanPerPegawaiSheet($spreadsheet, $dateFrom, $dateTo);

        // Sheet 3: Per Minggu (7 hari)
        $this->createEfektivitasLaporanPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, 'weekly');

        // Sheet 4: Per Hari
        $this->createEfektivitasLaporanPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, 'daily');

        // Set sheet 1 sebagai active sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Generate filename
        $filename = 'Efektivitas_Laporan_Kekurangan_' . date('Y-m-d', strtotime($dateFrom)) . '_sd_' . date('Y-m-d', strtotime($dateTo)) . '.xlsx';

        // Download
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Create Excel Summary Sheet for Laporan Kekurangan
     */
    private function createEfektivitasLaporanSummarySheet($spreadsheet, $dateFrom, $dateTo)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Total');

        // Header
        $sheet->setCellValue('A1', 'RINGKASAN EFEKTIVITAS LAPORAN KEKURANGAN BERKAS');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Periode
        $sheet->setCellValue('A2', 'Periode:');
        $sheet->setCellValue('B2', date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet->getStyle('A2')->getFont()->setBold(true);

        // Data
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $totalLaporan = DB::table('log_aktivitas')
            ->where('event_name', 'Laporan-Kekurangan-Riwayat')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $efektivitasTotal = $totalWorkingHours > 0 ? round($totalLaporan / $totalWorkingHours, 2) : 0;

        $sheet->setCellValue('A4', 'Total Hari Kerja (Senin-Jumat)');
        $sheet->setCellValue('B4', $totalWorkingDays);
        $sheet->setCellValue('A5', 'Total Jam Kerja (7.5 jam/hari)');
        $sheet->setCellValue('B5', $totalWorkingHours);
        $sheet->setCellValue('A6', 'Total Laporan Kekurangan Berkas');
        $sheet->setCellValue('B6', $totalLaporan);
        $sheet->setCellValue('A7', 'Efektivitas Total (laporan/jam)');
        $sheet->setCellValue('B7', $efektivitasTotal);

        // Styling
        $sheet->getStyle('A4:A7')->getFont()->setBold(true);
        $sheet->getStyle('A7:B7')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFE599');
        $sheet->getStyle('A7:B7')->getFont()->setBold(true);

        // Number format
        $sheet->getStyle('B4:B6')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0.0');
        $sheet->getStyle('B7')->getNumberFormat()->setFormatCode('#,##0.00');

        // Column width
        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(20);
    }

    /**
     * Create Excel Per Pegawai Sheet for Laporan Kekurangan
     */
    private function createEfektivitasLaporanPerPegawaiSheet($spreadsheet, $dateFrom, $dateTo)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ranking Pegawai');

        // Header
        $sheet->setCellValue('A1', 'RANKING PEGAWAI - EFEKTIVITAS LAPORAN KEKURANGAN BERKAS');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Periode
        $sheet->setCellValue('A2', 'Periode:');
        $sheet->setCellValue('B2', date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet->mergeCells('B2:E2');

        // Table header
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'NIP');
        $sheet->setCellValue('C4', 'Nama');
        $sheet->setCellValue('D4', 'Total Laporan');
        $sheet->setCellValue('E4', 'Efektivitas (lap/jam)');

        $sheet->getStyle('A4:E4')->getFont()->setBold(true);
        $sheet->getStyle('A4:E4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFC107');
        $sheet->getStyle('A4:E4')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A4:E4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Get data
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $results = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_laporan')
            )
            ->where('la.event_name', 'Laporan-Kekurangan-Riwayat')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'la.created_by_nama')
            ->orderByDesc('total_laporan')
            ->get();

        // Fill data
        $row = 5;
        $no = 1;
        foreach ($results as $item) {
            $efektivitas = $totalWorkingHours > 0 ? round($item->total_laporan / $totalWorkingHours, 2) : 0;

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $item->nip);
            $sheet->setCellValue('C' . $row, $item->nama);
            $sheet->setCellValue('D' . $row, $item->total_laporan);
            $sheet->setCellValue('E' . $row, $efektivitas);

            // Format
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $row++;
        }

        // Column width
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(22);

        // Borders
        if ($row > 5) {
            $sheet->getStyle('A4:E' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }
    }

    /**
     * Create Excel Per Periode Sheet for Laporan Kekurangan
     */
    private function createEfektivitasLaporanPerPeriodeSheet($spreadsheet, $dateFrom, $dateTo, $groupBy)
    {
        $sheet = $spreadsheet->createSheet();
        $title = $groupBy === 'weekly' ? 'Per Minggu (7 Hari)' : 'Per Hari';
        $sheet->setTitle($title);

        // Header
        $headerText = $groupBy === 'weekly' ? 'EFEKTIVITAS LAPORAN KEKURANGAN PER MINGGU (7 HARI)' : 'EFEKTIVITAS LAPORAN KEKURANGAN PER HARI';
        $sheet->setCellValue('A1', $headerText);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Periode
        $sheet->setCellValue('A2', 'Periode:');
        $sheet->setCellValue('B2', date('d/m/Y', strtotime($dateFrom)) . ' - ' . date('d/m/Y', strtotime($dateTo)));
        $sheet->mergeCells('B2:E2');

        // Table header
        $sheet->setCellValue('A4', 'Periode');
        $sheet->setCellValue('B4', 'Hari Kerja');
        $sheet->setCellValue('C4', 'Jam Kerja');
        $sheet->setCellValue('D4', 'Total Laporan');
        $sheet->setCellValue('E4', 'Efektivitas (lap/jam)');

        $sheet->getStyle('A4:E4')->getFont()->setBold(true);
        $sheet->getStyle('A4:E4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2196F3');
        $sheet->getStyle('A4:E4')->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A4:E4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Get data
        $efektivitasPerPeriode = $this->getEfektivitasLaporanPerPeriode($dateFrom, $dateTo, $groupBy);

        // Fill data
        $row = 5;
        foreach ($efektivitasPerPeriode as $item) {
            $sheet->setCellValue('A' . $row, $item['periode']);
            $sheet->setCellValue('B' . $row, $item['working_days']);
            $sheet->setCellValue('C' . $row, $item['working_hours']);
            $sheet->setCellValue('D' . $row, $item['total_laporan']);
            $sheet->setCellValue('E' . $row, $item['efektivitas']);

            // Format
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.0');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $row++;
        }

        // Column width
        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(22);

        // Borders
        if ($row > 5) {
            $sheet->getStyle('A4:E' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }
    }

    /**
     * Get Efektivitas Laporan Kekurangan Per Periode (weekly/daily)
     */
    private function getEfektivitasLaporanPerPeriode($dateFrom, $dateTo, $groupBy)
    {
        $result = [];

        if ($groupBy === 'daily') {
            $start = new \DateTime($dateFrom);
            $end = new \DateTime($dateTo);
            $interval = new \DateInterval('P1D');
            $period = new \DatePeriod($start, $interval, $end->modify('+1 day'));

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $dayOfWeek = (int)$date->format('N');

                // Skip weekend
                if ($dayOfWeek == 6 || $dayOfWeek == 7) {
                    continue;
                }

                $workingDays = 1;
                $workingHours = 7.5;

                // Hitung total laporan
                $totalLaporan = DB::table('log_aktivitas')
                    ->where('event_name', 'Laporan-Kekurangan-Riwayat')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [$dateStr . ' 00:00:00', $dateStr . ' 23:59:59'])
                    ->count();

                // Ambil day_name dari database
                $dayNameRecord = DB::table('log_aktivitas')
                    ->where('event_name', 'Laporan-Kekurangan-Riwayat')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [$dateStr . ' 00:00:00', $dateStr . ' 23:59:59'])
                    ->whereNotNull('day_name')
                    ->value('day_name');

                $dayName = $dayNameRecord ?? $this->getDayNameIndo($dayOfWeek);

                $result[] = [
                    'periode' => $dayName . ', ' . $date->format('d/m/Y'),
                    'working_days' => $workingDays,
                    'working_hours' => $workingHours,
                    'total_laporan' => $totalLaporan,
                    'efektivitas' => $workingHours > 0 ? round($totalLaporan / $workingHours, 2) : 0,
                ];
            }
        } elseif ($groupBy === 'weekly') {
            $start = new \DateTime($dateFrom);
            $end = new \DateTime($dateTo);

            $weekStart = clone $start;

            while ($weekStart <= $end) {
                $weekEnd = clone $weekStart;
                $weekEnd->modify('+6 days');

                if ($weekEnd > $end) {
                    $weekEnd = clone $end;
                }

                $workingDays = $this->calculateWorkingDays($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
                $workingHours = $workingDays * 7.5;

                $totalLaporan = DB::table('log_aktivitas')
                    ->where('event_name', 'Laporan-Kekurangan-Riwayat')
                    ->where('is_inject', 0)
                    ->whereBetween('created_at_log', [
                        $weekStart->format('Y-m-d') . ' 00:00:00',
                        $weekEnd->format('Y-m-d') . ' 23:59:59'
                    ])
                    ->count();

                $result[] = [
                    'periode' => 'Minggu ' . $weekStart->format('d/m/Y') . ' - ' . $weekEnd->format('d/m/Y'),
                    'working_days' => $workingDays,
                    'working_hours' => $workingHours,
                    'total_laporan' => $totalLaporan,
                    'efektivitas' => $workingHours > 0 ? round($totalLaporan / $workingHours, 2) : 0,
                ];

                $weekStart->modify('+7 days');
            }
        }

        return $result;
    }

    /**
     * Export Efektivitas Laporan Kekurangan to PDF
     */
    public function exportEfektivitasLaporanPdf(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!$dateFrom || !$dateTo) {
            return back()->with('error', 'Tanggal mulai dan akhir harus diisi');
        }

        // Get data
        $totalWorkingDays = $this->calculateWorkingDays($dateFrom, $dateTo);
        $totalWorkingHours = $totalWorkingDays * 7.5;

        $totalLaporan = DB::table('log_aktivitas')
            ->where('event_name', 'Laporan-Kekurangan-Riwayat')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->count();

        $efektivitasTotal = $totalWorkingHours > 0 ? number_format($totalLaporan / $totalWorkingHours, 2) : '0.00';

        // Hitung jumlah pegawai unik
        $totalPegawai = DB::table('log_aktivitas')
            ->where('event_name', 'Laporan-Kekurangan-Riwayat')
            ->where('is_inject', 0)
            ->whereBetween('created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->distinct('created_by_nip')
            ->count('created_by_nip');

        // METRIK TAMBAHAN
        $totalWorkingMinutes = $totalWorkingHours * 60;
        $avgPerPerson = $totalPegawai > 0 ? round($totalLaporan / $totalPegawai, 2) : 0;
        $minutesPerLaporan = $totalLaporan > 0 ? round($totalWorkingMinutes / $totalLaporan, 2) : 0;
        $laporanPerMinute = $totalWorkingMinutes > 0 ? round($totalLaporan / $totalWorkingMinutes, 4) : 0;

        // Efektivitas per pegawai
        $results = DB::table('log_aktivitas as la')
            ->leftJoin('dms_pns as dp', 'la.created_by_nip', '=', 'dp.nip')
            ->select(
                'la.created_by_nip as nip',
                DB::raw('COALESCE(dp.nama, la.created_by_nama, la.created_by_nip) as nama'),
                DB::raw('COUNT(*) as total_laporan')
            )
            ->where('la.event_name', 'Laporan-Kekurangan-Riwayat')
            ->where('la.is_inject', 0)
            ->whereBetween('la.created_at_log', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->groupBy('la.created_by_nip', 'dp.nama', 'la.created_by_nama')
            ->orderByDesc('total_laporan')
            ->get();

        $efektivitasPerPegawai = $results->map(function ($item) use ($totalWorkingHours) {
            return [
                'nip' => $item->nip,
                'nama' => $item->nama,
                'total_laporan' => $item->total_laporan,
                'efektivitas' => $totalWorkingHours > 0 ? number_format($item->total_laporan / $totalWorkingHours, 2) : '0.00',
            ];
        })->toArray();

        // Efektivitas per minggu
        $efektivitasPerMinggu = $this->getEfektivitasLaporanPerPeriode($dateFrom, $dateTo, 'weekly');

        // Efektivitas per hari
        $efektivitasPerHari = $this->getEfektivitasLaporanPerPeriode($dateFrom, $dateTo, 'daily');

        // Prepare data for PDF with underscore variable names
        $date_from = $dateFrom;
        $date_to = $dateTo;
        $total_working_days = $totalWorkingDays;
        $total_working_hours = $totalWorkingHours;
        $total_working_minutes = $totalWorkingMinutes;
        $total_laporan = $totalLaporan;
        $total_pegawai = $totalPegawai;
        $efektivitas_total = $efektivitasTotal;
        $avg_per_person = $avgPerPerson;
        $minutes_per_laporan = $minutesPerLaporan;
        $laporan_per_minute = $laporanPerMinute;
        $efektivitas_per_pegawai = $efektivitasPerPegawai;
        $efektivitas_per_minggu = $efektivitasPerMinggu;
        $efektivitas_per_hari = $efektivitasPerHari;

        // Generate PDF
        $pdf = PDF::loadView('pdf.efektivitas-laporan-kekurangan', compact(
            'date_from',
            'date_to',
            'total_working_days',
            'total_working_hours',
            'total_working_minutes',
            'total_laporan',
            'total_pegawai',
            'efektivitas_total',
            'avg_per_person',
            'minutes_per_laporan',
            'laporan_per_minute',
            'efektivitas_per_pegawai',
            'efektivitas_per_minggu',
            'efektivitas_per_hari'
        ));

        $pdf->setPaper('A4', 'portrait');

        $filename = 'Efektivitas_Laporan_Kekurangan_' . date('Y-m-d', strtotime($dateFrom)) . '_sd_' . date('Y-m-d', strtotime($dateTo)) . '.pdf';

        return $pdf->download($filename);
    }
}
