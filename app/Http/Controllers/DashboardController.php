<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DmsUpload;
use App\Models\DmsPns;
use App\Models\DmsPnsScoreLog;
use App\Models\DmsInstansiScore;
use App\Models\DmsNasional;
use App\Models\MonevDmsInstansiScore;
use App\Models\MonevDmsNasional;
use App\Models\MonevDmsSkorRata2Nasional;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function dms(Request $request)
    {
        $uploads = DmsUpload::orderBy('upload_date', 'desc')->paginate(10);

        // Get statistics
        $stats = [
            'total_uploads' => DmsUpload::count(),
            'total_pns' => DmsPns::count(),
            'total_instansi_calculated' => DmsInstansiScore::where('calculation_status', 'completed')
                ->distinct('instansi_id')
                ->count('instansi_id'),
            'latest_upload' => DmsUpload::latest('upload_date')->first(),
        ];

        // Get search parameter
        $search = $request->input('search');

        // Get all instansi with their latest scores (for overview tab)
        $query = DB::table('dms_instansi_scores as dis')
            ->join(DB::raw('(SELECT instansi_id, MAX(calculated_at) as max_date
                            FROM dms_instansi_scores
                            WHERE calculation_status = "completed"
                            GROUP BY instansi_id) as latest'), function($join) {
                $join->on('dis.instansi_id', '=', 'latest.instansi_id')
                     ->on('dis.calculated_at', '=', 'latest.max_date');
            })
            ->join('dms_uploads', 'dis.upload_id', '=', 'dms_uploads.id')
            ->select(
                'dis.instansi_id',
                'dis.instansi_nama',
                'dis.total_pns',
                'dis.skor_instansi_calculated_system',
                'dis.skor_instansi_calculated_csv',
                'dis.status_kelengkapan',
                'dis.calculated_at',
                'dis.upload_id',
                'dms_uploads.upload_date'
            );

        // Apply search filter if exists
        if ($search) {
            $query->where('dis.instansi_nama', 'like', '%' . $search . '%');
        }

        // Batasi hanya 5 instansi untuk list default (tanpa search)
        $perPage = $search ? 15 : 5;
        $calculatedInstansi = $query->orderBy('dis.calculated_at', 'desc')
            ->paginate($perPage)
            ->appends(['search' => $search]);

        // Get TOP 5 instansi (highest scores)
        $topInstansi = DB::table('dms_instansi_scores as dis')
            ->join(DB::raw('(SELECT instansi_id, MAX(calculated_at) as max_date
                            FROM dms_instansi_scores
                            WHERE calculation_status = "completed"
                            GROUP BY instansi_id) as latest'), function($join) {
                $join->on('dis.instansi_id', '=', 'latest.instansi_id')
                     ->on('dis.calculated_at', '=', 'latest.max_date');
            })
            ->select('dis.instansi_nama', 'dis.skor_instansi_calculated_system')
            ->orderBy('dis.skor_instansi_calculated_system', 'desc')
            ->limit(5)
            ->get();

        // Get BOTTOM 5 instansi (lowest scores)
        $bottomInstansi = DB::table('dms_instansi_scores as dis')
            ->join(DB::raw('(SELECT instansi_id, MAX(calculated_at) as max_date
                            FROM dms_instansi_scores
                            WHERE calculation_status = "completed"
                            GROUP BY instansi_id) as latest'), function($join) {
                $join->on('dis.instansi_id', '=', 'latest.instansi_id')
                     ->on('dis.calculated_at', '=', 'latest.max_date');
            })
            ->select('dis.instansi_nama', 'dis.skor_instansi_calculated_system')
            ->orderBy('dis.skor_instansi_calculated_system', 'asc')
            ->limit(5)
            ->get();

        // Get score distribution summary
        $scoreDistribution = DB::table('dms_pns_score_log')
            ->select(
                DB::raw('COUNT(CASE WHEN skor_calculated >= 80 THEN 1 END) as sangat_baik'),
                DB::raw('COUNT(CASE WHEN skor_calculated >= 60 AND skor_calculated < 80 THEN 1 END) as baik'),
                DB::raw('COUNT(CASE WHEN skor_calculated >= 40 AND skor_calculated < 60 THEN 1 END) as cukup'),
                DB::raw('COUNT(CASE WHEN skor_calculated < 40 THEN 1 END) as kurang')
            )
            ->first();

        // Status kelengkapan distribution (untuk pie chart)
        $kelengkapanDistribution = DB::table('dms_pns_score_log')
            ->select('status_kelengkapan', DB::raw('COUNT(*) as total'))
            ->whereNotNull('status_kelengkapan')
            ->groupBy('status_kelengkapan')
            ->get()
            ->keyBy('status_kelengkapan');

        // Get latest national score data
        $nasionalScore = DmsNasional::where('calculation_status', 'completed')
            ->orderBy('calculated_at', 'desc')
            ->first();

        // Get Monev data (from manual CSV upload)
        // Check if user selected a specific date
        $selectedMonevDate = $request->input('monev_date');

        // Get all monev upload dates for dropdown
        $monevUploads = MonevDmsNasional::orderBy('upload_date', 'desc')->get();

        $monevNasionalScore = null;
        $monevTopInstansi = collect();
        $monevBottomInstansi = collect();

        // If user selected a date, use that, otherwise use the latest
        if ($selectedMonevDate) {
            $monevNasionalScore = MonevDmsNasional::where('upload_date', $selectedMonevDate)->first();
        } else {
            $monevNasionalScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
        }

        // Get search parameter for monev instansi
        $monevSearch = $request->input('monev_search');
        $monevKantorRegionalStats = collect();

        if ($monevNasionalScore) {
            // Get top 5 for summary
            $monevTopInstansi = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date)
                ->orderBy('monev_skor_instansi', 'desc')
                ->limit(5)
                ->get();

            // Get bottom 5 for summary
            $monevBottomInstansi = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date)
                ->orderBy('monev_skor_instansi', 'asc')
                ->limit(5)
                ->get();

            // Get all instansi with pagination and search for detailed table
            $monevAllInstansiQuery = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date);

            if ($monevSearch) {
                $monevAllInstansiQuery->where('nama_instansi', 'like', '%' . $monevSearch . '%');
            }

            $monevAllInstansi = $monevAllInstansiQuery
                ->orderBy('monev_skor_instansi', 'desc')
                ->paginate(10)
                ->appends(['monev_date' => $selectedMonevDate, 'monev_search' => $monevSearch]);

            // Get Kantor Regional statistics dengan CASE untuk handle NULL dan \N jadi 0
            // Menggunakan subquery untuk "clean" data dulu baru di-aggregate
            $monevKantorRegionalStats = DB::table(DB::raw('(
                SELECT
                    CASE
                        WHEN kantor_regional_id IS NULL THEN "0"
                        WHEN kantor_regional_id = "\\N" THEN "0"
                        WHEN kantor_regional_id = "\\\\N" THEN "0"
                        ELSE kantor_regional_id
                    END as clean_kantor_regional_id,
                    monev_skor_instansi,
                    monev_status_kelengkapan
                FROM monev_dms_instansi_score
                WHERE upload_date = "' . $monevNasionalScore->upload_date . '"
            ) as cleaned_data'))
                ->select(
                    'clean_kantor_regional_id as kantor_regional_id',
                    DB::raw('COUNT(*) as total_instansi'),
                    DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
                )
                ->groupBy('clean_kantor_regional_id')
                ->orderBy('clean_kantor_regional_id', 'asc')
                ->get();

            // Period Comparison Analysis (only if there are at least 2 periods)
            $comparisonData = null;
            if ($monevUploads->count() >= 2) {
                // Check if user selected custom periods to compare
                $comparePeriodStart = $request->input('compare_period_start');
                $comparePeriodEnd = $request->input('compare_period_end');

                // If user selected custom periods, use those
                if ($comparePeriodStart && $comparePeriodEnd) {
                    $previousPeriod = $comparePeriodStart;
                    $currentPeriod = $comparePeriodEnd;
                } else {
                    // Otherwise, compare current period with previous period
                    $currentPeriod = $monevNasionalScore->upload_date;

                    // Find previous period (periode sebelum current)
                    $previousPeriodData = MonevDmsNasional::where('upload_date', '<', $currentPeriod)
                        ->orderBy('upload_date', 'desc')
                        ->first();

                    if (!$previousPeriodData) {
                        $previousPeriod = null;
                    } else {
                        $previousPeriod = $previousPeriodData->upload_date;
                    }
                }

                if ($previousPeriod) {

                    // Get scores from both periods
                    $currentScores = MonevDmsInstansiScore::where('upload_date', $currentPeriod)
                        ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi')
                        ->get()
                        ->keyBy('id_instansi');

                    $previousScores = MonevDmsInstansiScore::where('upload_date', $previousPeriod)
                        ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi')
                        ->get()
                        ->keyBy('id_instansi');

                    // Calculate changes
                    $changes = [];
                    $countNaik = 0;
                    $countTurun = 0;
                    $countStagnan = 0;

                    foreach ($currentScores as $idInstansi => $current) {
                        if (isset($previousScores[$idInstansi])) {
                            $previous = $previousScores[$idInstansi];

                            // Truncate skor sebelum dan sekarang ke 1 desimal
                            $skorSebelumTruncated = floor($previous->monev_skor_instansi * 10) / 10;
                            $skorSekarangTruncated = floor($current->monev_skor_instansi * 10) / 10;

                            // Hitung selisih dari skor yang sudah di-truncate
                            $scoreDiff = $skorSekarangTruncated - $skorSebelumTruncated;

                            // Round dulu ke 6 desimal untuk menghindari floating point error, baru truncate ke 1 desimal
                            $scoreDiffRounded = round($scoreDiff, 6);
                            $scoreDiffTruncated = floor($scoreDiffRounded * 10) / 10;

                            $changes[] = [
                                'id_instansi' => $idInstansi,
                                'nama_instansi' => $current->nama_instansi,
                                'skor_sebelum' => $skorSebelumTruncated,
                                'skor_sekarang' => $skorSekarangTruncated,
                                'perubahan' => $scoreDiffTruncated,
                                'perubahan_abs' => abs($scoreDiffTruncated)
                            ];

                            // Count trends
                            if ($scoreDiffTruncated > 0.5) {
                                $countNaik++;
                            } elseif ($scoreDiffTruncated < -0.5) {
                                $countTurun++;
                            } else {
                                $countStagnan++;
                            }
                        }
                    }

                    // Sort by perubahan descending for biggest increases
                    usort($changes, function($a, $b) {
                        return $b['perubahan'] <=> $a['perubahan'];
                    });

                    // Get top 5 biggest increases (kenaikan terbesar)
                    $biggestChanges = array_slice($changes, 0, 5);

                    // Get top 5 smallest increases/stagnan (kenaikan terkecil atau stagnan)
                    $mostStable = array_slice(array_reverse($changes), 0, 5);

                    $comparisonData = [
                        'current_period' => $currentPeriod,
                        'previous_period' => $previousPeriod,
                        'biggest_changes' => $biggestChanges,
                        'most_stable' => $mostStable,
                        'count_naik' => $countNaik,
                        'count_turun' => $countTurun,
                        'count_stagnan' => $countStagnan,
                        'total_compared' => count($changes)
                    ];
                }
            }
        } else {
            $monevAllInstansi = collect();
            $comparisonData = null;
        }

        // Get skor rata2 nasional uploads
        $skorNasionalUploads = MonevDmsSkorRata2Nasional::orderBy('upload_date', 'desc')->get();

        // Get skor nasional based on selected date (same logic as monevNasionalScore)
        $selectedSkorNasional = null;
        if ($selectedMonevDate) {
            $selectedSkorNasional = MonevDmsSkorRata2Nasional::where('upload_date', $selectedMonevDate)->first();
        } else {
            $selectedSkorNasional = MonevDmsSkorRata2Nasional::orderBy('upload_date', 'desc')->first();
        }

        return view('dashboard.dms', compact(
            'uploads',
            'stats',
            'calculatedInstansi',
            'scoreDistribution',
            'kelengkapanDistribution',
            'search',
            'topInstansi',
            'bottomInstansi',
            'nasionalScore',
            'monevNasionalScore',
            'monevTopInstansi',
            'monevBottomInstansi',
            'monevUploads',
            'selectedMonevDate',
            'monevAllInstansi',
            'monevSearch',
            'monevKantorRegionalStats',
            'comparisonData',
            'skorNasionalUploads',
            'selectedSkorNasional'
        ));
    }

    public function exportMonevPdf(Request $request)
    {
        // Get selected date or use latest
        $selectedMonevDate = $request->input('monev_date');

        if ($selectedMonevDate) {
            $monevNasionalScore = MonevDmsNasional::where('upload_date', $selectedMonevDate)->first();
        } else {
            $monevNasionalScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
        }

        if (!$monevNasionalScore) {
            return back()->with('error', 'Tidak ada data Monev untuk di-export');
        }

        // Get top 5 instansi
        $monevTopInstansi = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date)
            ->orderBy('monev_skor_instansi', 'desc')
            ->limit(5)
            ->get();

        // Get bottom 5 instansi
        $monevBottomInstansi = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date)
            ->orderBy('monev_skor_instansi', 'asc')
            ->limit(5)
            ->get();

        // Get Kantor Regional statistics dengan subquery untuk aggregate data yang sudah di-clean
        $monevKantorRegionalStats = DB::table(DB::raw('(
                SELECT
                    CASE
                        WHEN kantor_regional_id IS NULL THEN "0"
                        WHEN kantor_regional_id = "\\N" THEN "0"
                        WHEN kantor_regional_id = "\\\\N" THEN "0"
                        ELSE kantor_regional_id
                    END as clean_kantor_regional_id,
                    monev_skor_instansi,
                    monev_status_kelengkapan
                FROM monev_dms_instansi_score
                WHERE upload_date = "' . $monevNasionalScore->upload_date . '"
            ) as cleaned_data'))
            ->select(
                'clean_kantor_regional_id as kantor_regional_id',
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
            )
            ->groupBy('clean_kantor_regional_id')
            ->orderBy('clean_kantor_regional_id', 'asc')
            ->get();

        // Get Skor Rata-rata Nasional for the same date
        $selectedSkorNasional = MonevDmsSkorRata2Nasional::where('upload_date', $monevNasionalScore->upload_date)->first();

        // Generate PDF
        $pdf = \PDF::loadView('dashboard.monev-pdf', compact(
            'monevNasionalScore',
            'monevTopInstansi',
            'monevBottomInstansi',
            'monevKantorRegionalStats',
            'selectedSkorNasional'
        ));

        $pdf->setPaper('a4', 'portrait');

        $fileName = 'Laporan_Monev_Skor_' . \Carbon\Carbon::parse($monevNasionalScore->upload_date)->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    public function comparePeriods(Request $request)
    {
        $comparePeriodStart = $request->input('compare_period_start');
        $comparePeriodEnd = $request->input('compare_period_end');

        if (!$comparePeriodStart || !$comparePeriodEnd) {
            return response()->json(['error' => 'Pilih kedua periode untuk dibandingkan'], 400);
        }

        $previousPeriod = $comparePeriodStart;
        $currentPeriod = $comparePeriodEnd;

        // Get skor nasional from both periods
        $skorNasionalCurrent = MonevDmsSkorRata2Nasional::where('upload_date', $currentPeriod)->first();
        $skorNasionalPrevious = MonevDmsSkorRata2Nasional::where('upload_date', $previousPeriod)->first();

        // Get scores from both periods
        $currentScores = MonevDmsInstansiScore::where('upload_date', $currentPeriod)
            ->select('id_instansi', 'nama_instansi', 'kantor_regional_id', 'monev_skor_instansi', 'monev_status_kelengkapan',
                     'jumlah_asn', 'sangat_lengkap', 'lengkap', 'cukup_lengkap', 'kurang_lengkap')
            ->get()
            ->keyBy('id_instansi');

        $previousScores = MonevDmsInstansiScore::where('upload_date', $previousPeriod)
            ->select('id_instansi', 'nama_instansi', 'kantor_regional_id', 'monev_skor_instansi', 'monev_status_kelengkapan',
                     'jumlah_asn', 'sangat_lengkap', 'lengkap', 'cukup_lengkap', 'kurang_lengkap')
            ->get()
            ->keyBy('id_instansi');

        // Helper function to get category rank
        $getCategoryRank = function($category) {
            $ranks = [
                'Sangat Lengkap' => 4,
                'Lengkap' => 3,
                'Cukup Lengkap' => 2,
                'Kurang Lengkap' => 1
            ];
            return $ranks[$category] ?? 0;
        };

        // Calculate changes
        $changes = [];
        $countNaik = 0;
        $countTurun = 0;
        $countStagnan = 0;
        $countKategoriNaik = 0;
        $countKategoriTurun = 0;
        $countKategoriStagnan = 0;

        foreach ($currentScores as $idInstansi => $current) {
            if (isset($previousScores[$idInstansi])) {
                $previous = $previousScores[$idInstansi];

                // Truncate skor sebelum dan sekarang ke 1 desimal
                $skorSebelumTruncated = floor($previous->monev_skor_instansi * 10) / 10;
                $skorSekarangTruncated = floor($current->monev_skor_instansi * 10) / 10;

                // Hitung selisih dari skor yang sudah di-truncate
                $scoreDiff = $skorSekarangTruncated - $skorSebelumTruncated;

                // Round dulu ke 6 desimal untuk menghindari floating point error, baru truncate ke 1 desimal
                $scoreDiffRounded = round($scoreDiff, 6);
                $scoreDiffTruncated = floor($scoreDiffRounded * 10) / 10;

                $changes[] = [
                    'id_instansi' => $idInstansi,
                    'nama_instansi' => $current->nama_instansi,
                    'kantor_regional_id' => $current->kantor_regional_id,
                    'skor_sebelum' => $skorSebelumTruncated,
                    'skor_sekarang' => $skorSekarangTruncated,
                    'perubahan' => $scoreDiffTruncated,
                    'perubahan_abs' => abs($scoreDiffTruncated),
                    // Kelengkapan data periode sebelumnya
                    'jumlah_asn_sebelum' => $previous->jumlah_asn ?? 0,
                    'sangat_lengkap_sebelum' => $previous->sangat_lengkap ?? 0,
                    'lengkap_sebelum' => $previous->lengkap ?? 0,
                    'cukup_lengkap_sebelum' => $previous->cukup_lengkap ?? 0,
                    'kurang_lengkap_sebelum' => $previous->kurang_lengkap ?? 0,
                    // Kelengkapan data periode sekarang
                    'jumlah_asn_sekarang' => $current->jumlah_asn ?? 0,
                    'sangat_lengkap_sekarang' => $current->sangat_lengkap ?? 0,
                    'lengkap_sekarang' => $current->lengkap ?? 0,
                    'cukup_lengkap_sekarang' => $current->cukup_lengkap ?? 0,
                    'kurang_lengkap_sekarang' => $current->kurang_lengkap ?? 0,
                    // Status kelengkapan
                    'status_kelengkapan_sebelum' => $previous->monev_status_kelengkapan,
                    'status_kelengkapan_sekarang' => $current->monev_status_kelengkapan
                ];

                // Count trends - Stagnan hanya jika benar-benar 0 setelah truncate
                if ($scoreDiffTruncated > 0) {
                    $countNaik++;
                } elseif ($scoreDiffTruncated < 0) {
                    $countTurun++;
                } else {
                    $countStagnan++;
                }

                // Count category changes
                $previousCategoryRank = $getCategoryRank($previous->monev_status_kelengkapan);
                $currentCategoryRank = $getCategoryRank($current->monev_status_kelengkapan);

                if ($currentCategoryRank > $previousCategoryRank) {
                    $countKategoriNaik++;
                } elseif ($currentCategoryRank < $previousCategoryRank) {
                    $countKategoriTurun++;
                } else {
                    $countKategoriStagnan++;
                }
            }
        }

        // Sort by perubahan descending for biggest increases
        usort($changes, function($a, $b) {
            return $b['perubahan'] <=> $a['perubahan'];
        });

        // Get top 5 biggest increases (kenaikan terbesar)
        $biggestChanges = array_slice($changes, 0, 5);

        // Get top 5 smallest increases/stagnan (kenaikan terkecil atau stagnan)
        $mostStable = array_slice(array_reverse($changes), 0, 5);

        // Get Kantor Regional statistics with comparison
        $kanregStats = DB::table('monev_dms_instansi_score as current')
            ->join('monev_dms_instansi_score as previous', function($join) use ($previousPeriod) {
                $join->on('current.id_instansi', '=', 'previous.id_instansi')
                     ->where('previous.upload_date', '=', $previousPeriod);
            })
            ->where('current.upload_date', $currentPeriod)
            ->select(
                DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END as kantor_regional_id'),
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(previous.monev_skor_instansi) as skor_sebelumnya'),
                DB::raw('AVG(current.monev_skor_instansi) as skor_sesudahnya'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi > 0 THEN 1 ELSE 0 END) as naik'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi < 0 THEN 1 ELSE 0 END) as turun'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi = 0 THEN 1 ELSE 0 END) as stagnan'),
                DB::raw('AVG(current.monev_skor_instansi - previous.monev_skor_instansi) as avg_perubahan')
            )
            ->groupBy(DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END'))
            ->orderBy(DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END'))
            ->get()
            ->map(function($stat) {
                // Set nama kanreg - semua kantor regional termasuk 00 adalah valid
                $stat->nama_kanreg = 'Kantor Regional ' . str_pad($stat->kantor_regional_id, 2, '0', STR_PAD_LEFT);

                // Round dulu ke 6 desimal untuk menghindari floating point error, baru truncate ke 1 desimal
                $perubahanRounded = round($stat->avg_perubahan, 6);
                $perubahanTruncated = floor($perubahanRounded * 10) / 10;

                // Tentukan status kanreg (Naik/Stagnan/Turun berdasarkan perubahan yang sudah di-truncate)
                if ($perubahanTruncated > 0) {
                    $stat->status = 'Naik';
                } elseif ($perubahanTruncated < 0) {
                    $stat->status = 'Turun';
                } else {
                    $stat->status = 'Stagnan';
                }

                return $stat;
            })
            ->toArray();

        // Calculate average skor instansi for both periods
        $avgSkorInstansiCurrent = $currentScores->avg('monev_skor_instansi');
        $avgSkorInstansiPrevious = $previousScores->avg('monev_skor_instansi');

        $comparisonData = [
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'biggest_changes' => $biggestChanges,
            'most_stable' => $mostStable,
            'all_comparisons' => $changes,
            'kanreg_stats' => array_values($kanregStats),
            'count_naik' => $countNaik,
            'count_turun' => $countTurun,
            'count_stagnan' => $countStagnan,
            'count_kategori_naik' => $countKategoriNaik,
            'count_kategori_turun' => $countKategoriTurun,
            'count_kategori_stagnan' => $countKategoriStagnan,
            'total_compared' => count($changes),
            // Skor Nasional comparison
            'skor_nasional_current' => $skorNasionalCurrent ? [
                'skor' => $skorNasionalCurrent->skor_rata2_nasional,
                'jumlah_asn' => $skorNasionalCurrent->jumlah_asn,
                'status' => $skorNasionalCurrent->status_kelengkapan,
                'kurang_lengkap' => $skorNasionalCurrent->kurang_lengkap ?? 0,
                'cukup_lengkap' => $skorNasionalCurrent->cukup_lengkap ?? 0,
                'lengkap' => $skorNasionalCurrent->lengkap ?? 0,
                'sangat_lengkap' => $skorNasionalCurrent->sangat_lengkap ?? 0,
            ] : null,
            'skor_nasional_previous' => $skorNasionalPrevious ? [
                'skor' => $skorNasionalPrevious->skor_rata2_nasional,
                'jumlah_asn' => $skorNasionalPrevious->jumlah_asn,
                'status' => $skorNasionalPrevious->status_kelengkapan,
                'kurang_lengkap' => $skorNasionalPrevious->kurang_lengkap ?? 0,
                'cukup_lengkap' => $skorNasionalPrevious->cukup_lengkap ?? 0,
                'lengkap' => $skorNasionalPrevious->lengkap ?? 0,
                'sangat_lengkap' => $skorNasionalPrevious->sangat_lengkap ?? 0,
            ] : null,
            // Average Skor Instansi comparison
            'avg_skor_instansi_current' => round($avgSkorInstansiCurrent, 2),
            'avg_skor_instansi_previous' => round($avgSkorInstansiPrevious, 2)
        ];

        return response()->json($comparisonData);
    }

    public function exportKanregExcel(Request $request)
    {
        $kanregId = $request->input('kanreg_id');
        $monevDate = $request->input('monev_date');

        // If no date provided, get the latest
        if (!$monevDate) {
            $latestScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
            $monevDate = $latestScore ? $latestScore->upload_date : null;
        }

        if (!$monevDate) {
            return back()->with('error', 'Tidak ada data monev yang tersedia');
        }

        // Debug log
        \Log::info('Excel Export - Kanreg ID: ' . $kanregId . ', Date: ' . $monevDate);

        // Get instansi data for this kanreg
        $instansiList = DB::table('monev_dms_instansi_score')
            ->where('upload_date', $monevDate)
            ->where('kantor_regional_id', $kanregId)
            ->orderBy('monev_skor_instansi', 'desc')
            ->get();

        \Log::info('Found instansi: ' . $instansiList->count());

        if ($instansiList->isEmpty()) {
            return back()->with('error', 'Tidak ada data instansi untuk Kantor Regional ini pada tanggal ' . $monevDate);
        }

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header info
        $sheet->setCellValue('A1', 'DAFTAR DETAIL INSTANSI PER KANTOR REGIONAL');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Kantor Regional: ' . $kanregId);
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Periode Penilaian: ' . \Carbon\Carbon::parse($monevDate)->format('d F Y'));
        $sheet->mergeCells('A3:D3');

        $sheet->setCellValue('A4', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A4:D4');

        // Table header
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Nama Instansi');
        $sheet->setCellValue('C6', 'Skor');
        $sheet->setCellValue('D6', 'Status Kelengkapan');

        $sheet->getStyle('A6:D6')->getFont()->setBold(true);
        $sheet->getStyle('A6:D6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');

        // Data
        $row = 7;
        $no = 1;
        foreach ($instansiList as $instansi) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $instansi->nama_instansi);
            $sheet->setCellValue('C' . $row, number_format(floor($instansi->monev_skor_instansi * 10) / 10, 1));
            $sheet->setCellValue('D' . $row, $instansi->monev_status_kelengkapan);

            // Color code status
            $statusColor = '';
            switch ($instansi->monev_status_kelengkapan) {
                case 'Sangat Lengkap':
                    $statusColor = 'FF92D050';
                    break;
                case 'Lengkap':
                    $statusColor = 'FF3498DB';
                    break;
                case 'Cukup Lengkap':
                    $statusColor = 'FFF39C12';
                    break;
                case 'Kurang Lengkap':
                    $statusColor = 'FFFF6666';
                    break;
            }

            if ($statusColor) {
                $sheet->getStyle('D' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);
            }

            $row++;
            $no++;
        }

        // Auto size columns
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(20);

        // Export using StreamedResponse
        $filename = 'Daftar_Instansi_Kanreg_' . str_replace(' ', '_', $kanregId) . '_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportKanregPdf(Request $request)
    {
        $kanregId = $request->input('kanreg_id');
        $monevDate = $request->input('monev_date');

        // If no date provided, get the latest
        if (!$monevDate) {
            $latestScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
            $monevDate = $latestScore ? $latestScore->upload_date : null;
        }

        if (!$monevDate) {
            return response()->json(['error' => 'Tidak ada data monev yang tersedia'], 404);
        }

        // Debug log
        \Log::info('PDF Export - Kanreg ID: ' . $kanregId . ', Date: ' . $monevDate);

        // Get instansi data for this kanreg
        $instansiList = DB::table('monev_dms_instansi_score')
            ->where('upload_date', $monevDate)
            ->where('kantor_regional_id', $kanregId)
            ->orderBy('monev_skor_instansi', 'desc')
            ->get();

        \Log::info('Found instansi for PDF: ' . $instansiList->count());

        if ($instansiList->isEmpty()) {
            return response()->json(['error' => 'Tidak ada data instansi untuk Kantor Regional ini pada tanggal ' . $monevDate], 404);
        }

        // Test: render HTML first
        try {
            $html = view('dashboard.kanreg-detail-pdf', compact('instansiList', 'kanregId', 'monevDate'))->render();
            \Log::info('HTML rendered successfully, length: ' . strlen($html));

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');

            $filename = 'Daftar_Instansi_Kanreg_' . str_replace(' ', '_', $kanregId) . '_' . date('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Error: ' . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function exportAllInstansiExcel(Request $request)
    {
        $monevDate = $request->input('monev_date');

        // If no date provided, get the latest
        if (!$monevDate) {
            $latestScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
            $monevDate = $latestScore ? $latestScore->upload_date : null;
        }

        if (!$monevDate) {
            return back()->with('error', 'Tidak ada data monev yang tersedia');
        }

        // Get all instansi data
        $instansiList = DB::table('monev_dms_instansi_score')
            ->where('upload_date', $monevDate)
            ->orderBy('monev_skor_instansi', 'desc')
            ->get();

        if ($instansiList->isEmpty()) {
            return back()->with('error', 'Tidak ada data instansi untuk periode ini');
        }

        // Get skor rata-rata nasional for this date
        $skorNasional = MonevDmsSkorRata2Nasional::where('upload_date', $monevDate)->first();

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header info
        $sheet->setCellValue('A1', 'DAFTAR SEMUA INSTANSI - MONEV DMS');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode Penilaian: ' . \Carbon\Carbon::parse($monevDate)->format('d F Y'));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A3:J3');

        $sheet->setCellValue('A4', 'Total Instansi: ' . $instansiList->count());
        $sheet->mergeCells('A4:J4');
        $sheet->getStyle('A4')->getFont()->setBold(true);

        $currentRow = 6;

        // Skor Rata-rata DMS Nasional section
        if ($skorNasional) {
            $sheet->setCellValue('A' . $currentRow, 'SKOR RATA-RATA ARSIP DMS NASIONAL');
            $sheet->mergeCells('A' . $currentRow . ':J' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $currentRow)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF27AE60');
            $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'Skor Rata-rata DMS');
            $sheet->setCellValue('B' . $currentRow, number_format($skorNasional->skor_rata2_nasional, 2));
            $sheet->setCellValue('D' . $currentRow, 'Total ASN');
            $sheet->setCellValue('E' . $currentRow, number_format($skorNasional->jumlah_asn));
            $sheet->setCellValue('G' . $currentRow, 'Status Kelengkapan');
            $sheet->setCellValue('H' . $currentRow, $skorNasional->status_kelengkapan);
            $sheet->mergeCells('H' . $currentRow . ':J' . $currentRow);

            $sheet->getStyle('A' . $currentRow . ':J' . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $currentRow += 2;
        }

        // Rata-rata Skor Arsip Instansi section
        $avgScore = $instansiList->avg('monev_skor_instansi');
        $countSangatLengkap = $instansiList->where('monev_status_kelengkapan', 'Sangat Lengkap')->count();
        $countLengkap = $instansiList->where('monev_status_kelengkapan', 'Lengkap')->count();
        $countCukupLengkap = $instansiList->where('monev_status_kelengkapan', 'Cukup Lengkap')->count();
        $countKurangLengkap = $instansiList->where('monev_status_kelengkapan', 'Kurang Lengkap')->count();

        $sheet->setCellValue('A' . $currentRow, 'RATA-RATA SKOR ARSIP INSTANSI');
        $sheet->mergeCells('A' . $currentRow . ':J' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $currentRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF3498DB');
        $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Rata-rata Skor');
        $sheet->setCellValue('B' . $currentRow, number_format($avgScore, 2));
        $sheet->setCellValue('C' . $currentRow, 'Sangat Lengkap');
        $sheet->setCellValue('D' . $currentRow, $countSangatLengkap);
        $sheet->setCellValue('E' . $currentRow, 'Lengkap');
        $sheet->setCellValue('F' . $currentRow, $countLengkap);
        $sheet->setCellValue('G' . $currentRow, 'Cukup Lengkap');
        $sheet->setCellValue('H' . $currentRow, $countCukupLengkap);
        $sheet->setCellValue('I' . $currentRow, 'Kurang Lengkap');
        $sheet->setCellValue('J' . $currentRow, $countKurangLengkap);

        $sheet->getStyle('A' . $currentRow . ':J' . $currentRow)->getFont()->setBold(true);
        $sheet->getStyle('B' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('J' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $currentRow += 2;

        // Table header
        $sheet->setCellValue('A' . $currentRow, 'No');
        $sheet->setCellValue('B' . $currentRow, 'Nama Instansi');
        $sheet->setCellValue('C' . $currentRow, 'Kantor Regional');
        $sheet->setCellValue('D' . $currentRow, 'Skor');
        $sheet->setCellValue('E' . $currentRow, 'Jumlah ASN');
        $sheet->setCellValue('F' . $currentRow, 'Sangat Lengkap');
        $sheet->setCellValue('G' . $currentRow, 'Lengkap');
        $sheet->setCellValue('H' . $currentRow, 'Cukup Lengkap');
        $sheet->setCellValue('I' . $currentRow, 'Kurang Lengkap');
        $sheet->setCellValue('J' . $currentRow, 'Status Kelengkapan');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ];
        $sheet->getStyle('A' . $currentRow . ':J' . $currentRow)->applyFromArray($headerStyle);
        $currentRow++;

        // Data rows
        $row = $currentRow;
        $no = 1;
        foreach ($instansiList as $instansi) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $instansi->nama_instansi);
            $sheet->setCellValue('C' . $row, 'Kanreg ' . $instansi->kantor_regional_id);
            $sheet->setCellValue('D' . $row, number_format(floor($instansi->monev_skor_instansi * 10) / 10, 1));
            $sheet->setCellValue('E' . $row, number_format($instansi->jumlah_asn ?? 0));
            $sheet->setCellValue('F' . $row, number_format($instansi->sangat_lengkap ?? 0));
            $sheet->setCellValue('G' . $row, number_format($instansi->lengkap ?? 0));
            $sheet->setCellValue('H' . $row, number_format($instansi->cukup_lengkap ?? 0));
            $sheet->setCellValue('I' . $row, number_format($instansi->kurang_lengkap ?? 0));
            $sheet->setCellValue('J' . $row, $instansi->monev_status_kelengkapan);

            // Color code status
            $statusColor = '';
            switch ($instansi->monev_status_kelengkapan) {
                case 'Sangat Lengkap':
                    $statusColor = 'FF92D050';
                    break;
                case 'Lengkap':
                    $statusColor = 'FF3498DB';
                    break;
                case 'Cukup Lengkap':
                    $statusColor = 'FFF39C12';
                    break;
                case 'Kurang Lengkap':
                    $statusColor = 'FFFF6666';
                    break;
            }

            if ($statusColor) {
                $sheet->getStyle('J' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);
            }

            // Center align for specific columns
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // Auto size columns
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(20);

        // Export using StreamedResponse
        $filename = 'Daftar_Semua_Instansi_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportAllInstansiPdf(Request $request)
    {
        $monevDate = $request->input('monev_date');

        // If no date provided, get the latest
        if (!$monevDate) {
            $latestScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
            $monevDate = $latestScore ? $latestScore->upload_date : null;
        }

        if (!$monevDate) {
            return response()->json(['error' => 'Tidak ada data monev yang tersedia'], 404);
        }

        // Get all instansi data
        $instansiList = DB::table('monev_dms_instansi_score')
            ->where('upload_date', $monevDate)
            ->orderBy('monev_skor_instansi', 'desc')
            ->get();

        if ($instansiList->isEmpty()) {
            return response()->json(['error' => 'Tidak ada data instansi untuk periode ini'], 404);
        }

        // Get skor rata-rata nasional for this date
        $skorNasional = MonevDmsSkorRata2Nasional::where('upload_date', $monevDate)->first();

        // Render HTML first
        try {
            $html = view('dashboard.all-instansi-pdf', compact('instansiList', 'monevDate', 'skorNasional'))->render();

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('a4', 'landscape'); // Landscape karena banyak kolom

            $filename = 'Daftar_Semua_Instansi_' . date('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('PDF All Instansi Error: ' . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function exportKanregSummaryExcel(Request $request)
    {
        $monevDate = $request->input('monev_date');

        // If no date provided, get the latest
        if (!$monevDate) {
            $latestScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
            $monevDate = $latestScore ? $latestScore->upload_date : null;
        }

        if (!$monevDate) {
            return back()->with('error', 'Tidak ada data monev yang tersedia');
        }

        // Get Kantor Regional statistics dengan COALESCE untuk handle NULL jadi 0
        $kanregStats = DB::table('monev_dms_instansi_score')
            ->select(
                DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END as kantor_regional_id'),
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
            )
            ->where('upload_date', $monevDate)
            ->groupBy(DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END'))
            ->orderBy(DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END'), 'asc')
            ->get();

        if ($kanregStats->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk periode ini');
        }

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header info
        $sheet->setCellValue('A1', 'STATISTIK PER KANTOR REGIONAL - MONEV DMS');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode Penilaian: ' . \Carbon\Carbon::parse($monevDate)->format('d F Y'));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A3:J3');

        $sheet->setCellValue('A4', 'Total Kantor Regional: ' . $kanregStats->count());
        $sheet->mergeCells('A4:J4');
        $sheet->getStyle('A4')->getFont()->setBold(true);

        // Table header
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Kantor Regional');
        $sheet->setCellValue('C6', 'Total Instansi');
        $sheet->setCellValue('D6', 'Rata-rata Skor');
        $sheet->setCellValue('E6', 'Status Kelengkapan');
        $sheet->setCellValue('F6', 'Sangat Lengkap');
        $sheet->setCellValue('G6', 'Lengkap');
        $sheet->setCellValue('H6', 'Cukup Lengkap');
        $sheet->setCellValue('I6', 'Kurang Lengkap');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ];
        $sheet->getStyle('A6:I6')->applyFromArray($headerStyle);

        // Data rows
        $row = 7;
        $no = 1;
        foreach ($kanregStats as $kanreg) {
            // Determine dominant status
            $statusCounts = [
                'Sangat Lengkap' => $kanreg->count_sangat_lengkap,
                'Lengkap' => $kanreg->count_lengkap,
                'Cukup Lengkap' => $kanreg->count_cukup_lengkap,
                'Kurang Lengkap' => $kanreg->count_kurang_lengkap
            ];
            arsort($statusCounts);
            $dominantStatus = array_key_first($statusCounts);

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, 'Kantor Regional ' . str_pad($kanreg->kantor_regional_id, 2, '0', STR_PAD_LEFT));
            $sheet->setCellValue('C' . $row, $kanreg->total_instansi);
            $sheet->setCellValue('D' . $row, number_format(floor($kanreg->rata_rata_skor * 10) / 10, 1));
            $sheet->setCellValue('E' . $row, $dominantStatus);
            $sheet->setCellValue('F' . $row, $kanreg->count_sangat_lengkap);
            $sheet->setCellValue('G' . $row, $kanreg->count_lengkap);
            $sheet->setCellValue('H' . $row, $kanreg->count_cukup_lengkap);
            $sheet->setCellValue('I' . $row, $kanreg->count_kurang_lengkap);

            // Color code dominant status
            $statusColor = match($dominantStatus) {
                'Sangat Lengkap' => 'FF92D050',
                'Lengkap' => 'FF3498DB',
                'Cukup Lengkap' => 'FFF39C12',
                'Kurang Lengkap' => 'FFFF6666',
                default => 'FFCCCCCC'
            };

            $sheet->getStyle('E' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($statusColor);

            // Center align
            $sheet->getStyle('A' . $row . ':I' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // Auto size columns
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);

        // Export
        $filename = 'Statistik_Kantor_Regional_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportKanregSummaryPdf(Request $request)
    {
        $monevDate = $request->input('monev_date');

        // If no date provided, get the latest
        if (!$monevDate) {
            $latestScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
            $monevDate = $latestScore ? $latestScore->upload_date : null;
        }

        if (!$monevDate) {
            return response()->json(['error' => 'Tidak ada data monev yang tersedia'], 404);
        }

        // Get Kantor Regional statistics dengan COALESCE untuk handle NULL jadi 0
        $kanregStats = DB::table('monev_dms_instansi_score')
            ->select(
                DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END as kantor_regional_id'),
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
            )
            ->where('upload_date', $monevDate)
            ->groupBy(DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END'))
            ->orderBy(DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END'), 'asc')
            ->get();

        if ($kanregStats->isEmpty()) {
            return response()->json(['error' => 'Tidak ada data untuk periode ini'], 404);
        }

        // Render HTML
        try {
            $html = view('dashboard.kanreg-summary-pdf', compact('kanregStats', 'monevDate'))->render();

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');

            $filename = 'Statistik_Kantor_Regional_' . date('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Kanreg Summary Error: ' . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function exportComparisonExcel(Request $request)
    {
        $previousDate = $request->input('previous_date');
        $currentDate = $request->input('current_date');

        if (!$previousDate || !$currentDate) {
            return back()->with('error', 'Periode perbandingan tidak lengkap');
        }

        // Get skor nasional from both periods
        $skorNasionalCurrent = MonevDmsSkorRata2Nasional::where('upload_date', $currentDate)->first();
        $skorNasionalPrevious = MonevDmsSkorRata2Nasional::where('upload_date', $previousDate)->first();

        // Get scores from both periods
        $currentScores = MonevDmsInstansiScore::where('upload_date', $currentDate)
            ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi', 'monev_status_kelengkapan',
                     'jumlah_asn', 'sangat_lengkap', 'lengkap', 'cukup_lengkap', 'kurang_lengkap')
            ->get()
            ->keyBy('id_instansi');

        $previousScores = MonevDmsInstansiScore::where('upload_date', $previousDate)
            ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi', 'monev_status_kelengkapan',
                     'jumlah_asn', 'sangat_lengkap', 'lengkap', 'cukup_lengkap', 'kurang_lengkap')
            ->get()
            ->keyBy('id_instansi');

        // Calculate changes
        $changes = [];
        $countNaik = 0;
        $countTurun = 0;
        $countStagnan = 0;

        foreach ($currentScores as $idInstansi => $current) {
            if (isset($previousScores[$idInstansi])) {
                $previous = $previousScores[$idInstansi];

                // Truncate skor sebelum dan sekarang ke 1 desimal
                $skorSebelumTruncated = floor($previous->monev_skor_instansi * 10) / 10;
                $skorSekarangTruncated = floor($current->monev_skor_instansi * 10) / 10;

                // Hitung selisih dari skor yang sudah di-truncate
                $scoreDiff = $skorSekarangTruncated - $skorSebelumTruncated;

                // Round dulu ke 6 desimal untuk menghindari floating point error, baru truncate ke 1 desimal
                $scoreDiffRounded = round($scoreDiff, 6);
                $scoreDiffTruncated = floor($scoreDiffRounded * 10) / 10;

                // Determine status - Stagnan hanya jika benar-benar 0.0 setelah truncate
                $status = 'Stagnan';
                if ($scoreDiffTruncated > 0) {
                    $status = 'Naik';
                    $countNaik++;
                } elseif ($scoreDiffTruncated < 0) {
                    $status = 'Turun';
                    $countTurun++;
                } else {
                    $countStagnan++;
                }

                $changes[] = [
                    'nama_instansi' => $current->nama_instansi,
                    'skor_sebelum' => $skorSebelumTruncated,
                    'skor_sekarang' => $skorSekarangTruncated,
                    'perubahan' => $scoreDiffTruncated,  // Gunakan nilai yang sudah di-truncate
                    'status' => $status,
                    // Kelengkapan data sebelumnya
                    'jumlah_asn_sebelum' => $previous->jumlah_asn ?? 0,
                    'sangat_lengkap_sebelum' => $previous->sangat_lengkap ?? 0,
                    'lengkap_sebelum' => $previous->lengkap ?? 0,
                    'cukup_lengkap_sebelum' => $previous->cukup_lengkap ?? 0,
                    'kurang_lengkap_sebelum' => $previous->kurang_lengkap ?? 0,
                    // Kelengkapan data sekarang
                    'jumlah_asn_sekarang' => $current->jumlah_asn ?? 0,
                    'sangat_lengkap_sekarang' => $current->sangat_lengkap ?? 0,
                    'lengkap_sekarang' => $current->lengkap ?? 0,
                    'cukup_lengkap_sekarang' => $current->cukup_lengkap ?? 0,
                    'kurang_lengkap_sekarang' => $current->kurang_lengkap ?? 0,
                    // Status kelengkapan
                    'status_kelengkapan_sebelum' => $previous->monev_status_kelengkapan,
                    'status_kelengkapan_sekarang' => $current->monev_status_kelengkapan
                ];
            }
        }

        // Sort by change descending
        usort($changes, function($a, $b) {
            return $b['perubahan'] <=> $a['perubahan'];
        });

        // Calculate average skor instansi for both periods
        $avgSkorInstansiCurrent = $currentScores->avg('monev_skor_instansi');
        $avgSkorInstansiPrevious = $previousScores->avg('monev_skor_instansi');

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header info
        $sheet->setCellValue('A1', 'ANALISIS PERBANDINGAN PERIODE - MONEV DMS');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode Awal: ' . \Carbon\Carbon::parse($previousDate)->format('d F Y'));
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Periode Akhir: ' . \Carbon\Carbon::parse($currentDate)->format('d F Y'));
        $sheet->mergeCells('A3:K3');
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A4:K4');

        $currentRow = 6;

        // Skor Nasional Section
        if ($skorNasionalPrevious && $skorNasionalCurrent) {
            $sheet->setCellValue('A' . $currentRow, 'SKOR RATA-RATA ARSIP DMS NASIONAL');
            $sheet->mergeCells('A' . $currentRow . ':K' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $currentRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF27AE60');
            $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
            $currentRow++;

            $sheet->setCellValue('A' . $currentRow, 'Skor Sebelum');
            $sheet->setCellValue('B' . $currentRow, number_format($skorNasionalPrevious->skor_rata2_nasional, 2));
            $sheet->setCellValue('C' . $currentRow, 'Skor Sekarang');
            $sheet->setCellValue('D' . $currentRow, number_format($skorNasionalCurrent->skor_rata2_nasional, 2));
            $sheet->setCellValue('E' . $currentRow, 'Perubahan');
            $diff = $skorNasionalCurrent->skor_rata2_nasional - $skorNasionalPrevious->skor_rata2_nasional;
            $sheet->setCellValue('F' . $currentRow, number_format($diff, 2));
            $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Distribusi ASN Berdasarkan Status Kelengkapan
            $sheet->setCellValue('A' . $currentRow, 'Distribusi ASN Berdasarkan Status Kelengkapan:');
            $sheet->mergeCells('A' . $currentRow . ':K' . $currentRow);
            $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(10);
            $currentRow++;

            // Header tabel distribusi
            $sheet->setCellValue('A' . $currentRow, 'Status');
            $sheet->setCellValue('B' . $currentRow, 'Sebelum');
            $sheet->setCellValue('C' . $currentRow, 'Sekarang');
            $sheet->setCellValue('D' . $currentRow, 'Perubahan');
            $sheet->setCellValue('E' . $currentRow, 'Persentase');
            $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->getFont()->setBold(true);
            $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFBDC3C7');
            $currentRow++;

            // Kurang Lengkap
            $diffKurang = ($skorNasionalCurrent->kurang_lengkap ?? 0) - ($skorNasionalPrevious->kurang_lengkap ?? 0);
            $pctKurang = ($skorNasionalPrevious->kurang_lengkap ?? 0) > 0 ? (($diffKurang / ($skorNasionalPrevious->kurang_lengkap ?? 1)) * 100) : 0;
            $sheet->setCellValue('A' . $currentRow, 'Kurang Lengkap');
            $sheet->setCellValue('B' . $currentRow, number_format($skorNasionalPrevious->kurang_lengkap ?? 0));
            $sheet->setCellValue('C' . $currentRow, number_format($skorNasionalCurrent->kurang_lengkap ?? 0));
            $sheet->setCellValue('D' . $currentRow, ($diffKurang >= 0 ? '+' : '') . number_format($diffKurang));
            $sheet->setCellValue('E' . $currentRow, ($pctKurang >= 0 ? '+' : '') . number_format($pctKurang, 2) . '%');
            $colorKurang = $diffKurang > 0 ? 'FFE74C3C' : ($diffKurang < 0 ? 'FF27AE60' : 'FF000000');
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->getColor()->setARGB($colorKurang);
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Cukup Lengkap
            $diffCukup = ($skorNasionalCurrent->cukup_lengkap ?? 0) - ($skorNasionalPrevious->cukup_lengkap ?? 0);
            $pctCukup = ($skorNasionalPrevious->cukup_lengkap ?? 0) > 0 ? (($diffCukup / ($skorNasionalPrevious->cukup_lengkap ?? 1)) * 100) : 0;
            $sheet->setCellValue('A' . $currentRow, 'Cukup Lengkap');
            $sheet->setCellValue('B' . $currentRow, number_format($skorNasionalPrevious->cukup_lengkap ?? 0));
            $sheet->setCellValue('C' . $currentRow, number_format($skorNasionalCurrent->cukup_lengkap ?? 0));
            $sheet->setCellValue('D' . $currentRow, ($diffCukup >= 0 ? '+' : '') . number_format($diffCukup));
            $sheet->setCellValue('E' . $currentRow, ($pctCukup >= 0 ? '+' : '') . number_format($pctCukup, 2) . '%');
            $colorCukup = $diffCukup > 0 ? 'FF27AE60' : ($diffCukup < 0 ? 'FFE74C3C' : 'FF000000');
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->getColor()->setARGB($colorCukup);
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Lengkap
            $diffLengkap = ($skorNasionalCurrent->lengkap ?? 0) - ($skorNasionalPrevious->lengkap ?? 0);
            $pctLengkap = ($skorNasionalPrevious->lengkap ?? 0) > 0 ? (($diffLengkap / ($skorNasionalPrevious->lengkap ?? 1)) * 100) : 0;
            $sheet->setCellValue('A' . $currentRow, 'Lengkap');
            $sheet->setCellValue('B' . $currentRow, number_format($skorNasionalPrevious->lengkap ?? 0));
            $sheet->setCellValue('C' . $currentRow, number_format($skorNasionalCurrent->lengkap ?? 0));
            $sheet->setCellValue('D' . $currentRow, ($diffLengkap >= 0 ? '+' : '') . number_format($diffLengkap));
            $sheet->setCellValue('E' . $currentRow, ($pctLengkap >= 0 ? '+' : '') . number_format($pctLengkap, 2) . '%');
            $colorLengkap = $diffLengkap > 0 ? 'FF27AE60' : ($diffLengkap < 0 ? 'FFE74C3C' : 'FF000000');
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->getColor()->setARGB($colorLengkap);
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->setBold(true);
            $currentRow++;

            // Sangat Lengkap
            $diffSangat = ($skorNasionalCurrent->sangat_lengkap ?? 0) - ($skorNasionalPrevious->sangat_lengkap ?? 0);
            $pctSangat = ($skorNasionalPrevious->sangat_lengkap ?? 0) > 0 ? (($diffSangat / ($skorNasionalPrevious->sangat_lengkap ?? 1)) * 100) : 0;
            $sheet->setCellValue('A' . $currentRow, 'Sangat Lengkap');
            $sheet->setCellValue('B' . $currentRow, number_format($skorNasionalPrevious->sangat_lengkap ?? 0));
            $sheet->setCellValue('C' . $currentRow, number_format($skorNasionalCurrent->sangat_lengkap ?? 0));
            $sheet->setCellValue('D' . $currentRow, ($diffSangat >= 0 ? '+' : '') . number_format($diffSangat));
            $sheet->setCellValue('E' . $currentRow, ($pctSangat >= 0 ? '+' : '') . number_format($pctSangat, 2) . '%');
            $colorSangat = $diffSangat > 0 ? 'FF27AE60' : ($diffSangat < 0 ? 'FFE74C3C' : 'FF000000');
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->getColor()->setARGB($colorSangat);
            $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->getFont()->setBold(true);
            $currentRow += 2;
        }

        // Rata-rata Skor Instansi Section
        $sheet->setCellValue('A' . $currentRow, 'RATA-RATA SKOR ARSIP INSTANSI');
        $sheet->mergeCells('A' . $currentRow . ':K' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $currentRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF3498DB');
        $sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setARGB('FFFFFFFF');
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Rata-rata Sebelum');
        $sheet->setCellValue('B' . $currentRow, number_format($avgSkorInstansiPrevious, 2));
        $sheet->setCellValue('C' . $currentRow, 'Rata-rata Sekarang');
        $sheet->setCellValue('D' . $currentRow, number_format($avgSkorInstansiCurrent, 2));
        $sheet->setCellValue('E' . $currentRow, 'Perubahan');
        $diffAvg = $avgSkorInstansiCurrent - $avgSkorInstansiPrevious;
        $sheet->setCellValue('F' . $currentRow, number_format($diffAvg, 2));
        $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->getFont()->setBold(true);
        $currentRow += 2;

        // Summary Statistics
        $sheet->setCellValue('A' . $currentRow, 'Total Instansi: ' . count($changes));
        $sheet->mergeCells('A' . $currentRow . ':K' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $currentRow++;

        $sheet->setCellValue('A' . $currentRow, 'Instansi Naik: ' . $countNaik);
        $sheet->mergeCells('A' . $currentRow . ':C' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->getColor()->setARGB('FF27AE60');

        $sheet->setCellValue('D' . $currentRow, 'Instansi Stagnan: ' . $countStagnan);
        $sheet->mergeCells('D' . $currentRow . ':G' . $currentRow);
        $sheet->getStyle('D' . $currentRow)->getFont()->setBold(true)->getColor()->setARGB('FFF39C12');

        $sheet->setCellValue('H' . $currentRow, 'Instansi Turun: ' . $countTurun);
        $sheet->mergeCells('H' . $currentRow . ':K' . $currentRow);
        $sheet->getStyle('H' . $currentRow)->getFont()->setBold(true)->getColor()->setARGB('FFE74C3C');
        $currentRow += 2;

        // Table header - Row 1 (Merged headers)
        $headerRow1 = $currentRow;
        $sheet->setCellValue('A' . $headerRow1, 'No');
        $sheet->setCellValue('B' . $headerRow1, 'Nama Instansi');
        $sheet->setCellValue('C' . $headerRow1, 'Skor Sebelum');
        $sheet->setCellValue('D' . $headerRow1, 'Skor Sekarang');
        $sheet->setCellValue('E' . $headerRow1, 'Perubahan');
        $sheet->setCellValue('F' . $headerRow1, 'Kelengkapan Sebelum');
        $sheet->mergeCells('F' . $headerRow1 . ':J' . $headerRow1);
        $sheet->setCellValue('K' . $headerRow1, 'Kelengkapan Sekarang');
        $sheet->mergeCells('K' . $headerRow1 . ':O' . $headerRow1);
        $sheet->setCellValue('P' . $headerRow1, 'Status Kelengkapan');
        $sheet->setCellValue('Q' . $headerRow1, 'Status');

        $headerRow2 = $currentRow + 1;
        $sheet->setCellValue('F' . $headerRow2, 'Jml ASN');
        $sheet->setCellValue('G' . $headerRow2, 'S.Lengkap');
        $sheet->setCellValue('H' . $headerRow2, 'Lengkap');
        $sheet->setCellValue('I' . $headerRow2, 'C.Lengkap');
        $sheet->setCellValue('J' . $headerRow2, 'K.Lengkap');
        $sheet->setCellValue('K' . $headerRow2, 'Jml ASN');
        $sheet->setCellValue('L' . $headerRow2, 'S.Lengkap');
        $sheet->setCellValue('M' . $headerRow2, 'Lengkap');
        $sheet->setCellValue('N' . $headerRow2, 'C.Lengkap');
        $sheet->setCellValue('O' . $headerRow2, 'K.Lengkap');

        // Merge cells untuk kolom yang tidak punya sub-header
        $sheet->mergeCells('A' . $headerRow1 . ':A' . $headerRow2);
        $sheet->mergeCells('B' . $headerRow1 . ':B' . $headerRow2);
        $sheet->mergeCells('C' . $headerRow1 . ':C' . $headerRow2);
        $sheet->mergeCells('D' . $headerRow1 . ':D' . $headerRow2);
        $sheet->mergeCells('E' . $headerRow1 . ':E' . $headerRow2);
        $sheet->mergeCells('P' . $headerRow1 . ':P' . $headerRow2);
        $sheet->mergeCells('Q' . $headerRow1 . ':Q' . $headerRow2);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ];
        $sheet->getStyle('A' . $headerRow1 . ':Q' . $headerRow2)->applyFromArray($headerStyle);
        $currentRow += 2;

        // Data rows
        $row = $currentRow;
        $no = 1;
        foreach ($changes as $change) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $change['nama_instansi']);
            $sheet->setCellValue('C' . $row, number_format(floor($change['skor_sebelum'] * 10) / 10, 1));
            $sheet->setCellValue('D' . $row, number_format(floor($change['skor_sekarang'] * 10) / 10, 1));
            $sheet->setCellValue('E' . $row, number_format(floor($change['perubahan'] * 10) / 10, 1));
            // Kelengkapan Sebelum
            $sheet->setCellValue('F' . $row, number_format($change['jumlah_asn_sebelum']));
            $sheet->setCellValue('G' . $row, number_format($change['sangat_lengkap_sebelum']));
            $sheet->setCellValue('H' . $row, number_format($change['lengkap_sebelum']));
            $sheet->setCellValue('I' . $row, number_format($change['cukup_lengkap_sebelum']));
            $sheet->setCellValue('J' . $row, number_format($change['kurang_lengkap_sebelum']));
            // Kelengkapan Sekarang
            $sheet->setCellValue('K' . $row, number_format($change['jumlah_asn_sekarang']));
            $sheet->setCellValue('L' . $row, number_format($change['sangat_lengkap_sekarang']));
            $sheet->setCellValue('M' . $row, number_format($change['lengkap_sekarang']));
            $sheet->setCellValue('N' . $row, number_format($change['cukup_lengkap_sekarang']));
            $sheet->setCellValue('O' . $row, number_format($change['kurang_lengkap_sekarang']));
            $sheet->setCellValue('P' . $row, $change['status_kelengkapan_sekarang']);
            $sheet->setCellValue('Q' . $row, $change['status']);

            // Color code status
            $statusColor = match($change['status']) {
                'Naik' => 'FF92D050',
                'Turun' => 'FFFF6666',
                'Stagnan' => 'FFF39C12',
                default => 'FFCCCCCC'
            };

            $sheet->getStyle('Q' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($statusColor);

            // Center align
            foreach (range('A', 'Q') as $col) {
                $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }

            $row++;
            $no++;
        }

        // Auto size columns
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(12);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(12);
        $sheet->getColumnDimension('N')->setWidth(12);
        $sheet->getColumnDimension('O')->setWidth(12);
        $sheet->getColumnDimension('P')->setWidth(20);
        $sheet->getColumnDimension('Q')->setWidth(15);

        // Export
        $filename = 'Perbandingan_Periode_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportComparisonPdf(Request $request)
    {
        $previousDate = $request->input('previous_date');
        $currentDate = $request->input('current_date');

        if (!$previousDate || !$currentDate) {
            return response()->json(['error' => 'Periode perbandingan tidak lengkap'], 404);
        }

        // Get skor nasional from both periods
        $skorNasionalCurrent = MonevDmsSkorRata2Nasional::where('upload_date', $currentDate)->first();
        $skorNasionalPrevious = MonevDmsSkorRata2Nasional::where('upload_date', $previousDate)->first();

        // Get scores from both periods
        $currentScores = MonevDmsInstansiScore::where('upload_date', $currentDate)
            ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi', 'monev_status_kelengkapan',
                     'jumlah_asn', 'sangat_lengkap', 'lengkap', 'cukup_lengkap', 'kurang_lengkap')
            ->get()
            ->keyBy('id_instansi');

        $previousScores = MonevDmsInstansiScore::where('upload_date', $previousDate)
            ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi', 'monev_status_kelengkapan',
                     'jumlah_asn', 'sangat_lengkap', 'lengkap', 'cukup_lengkap', 'kurang_lengkap')
            ->get()
            ->keyBy('id_instansi');

        // Helper function to get category rank
        $getCategoryRank = function($category) {
            $ranks = [
                'Sangat Lengkap' => 4,
                'Lengkap' => 3,
                'Cukup Lengkap' => 2,
                'Kurang Lengkap' => 1
            ];
            return $ranks[$category] ?? 0;
        };

        // Calculate changes
        $changes = [];
        $countNaik = 0;
        $countTurun = 0;
        $countStagnan = 0;
        $countKategoriNaik = 0;
        $countKategoriTurun = 0;
        $countKategoriStagnan = 0;

        foreach ($currentScores as $idInstansi => $current) {
            if (isset($previousScores[$idInstansi])) {
                $previous = $previousScores[$idInstansi];

                // Truncate skor sebelum dan sekarang ke 1 desimal
                $skorSebelumTruncated = floor($previous->monev_skor_instansi * 10) / 10;
                $skorSekarangTruncated = floor($current->monev_skor_instansi * 10) / 10;

                // Hitung selisih dari skor yang sudah di-truncate
                $scoreDiff = $skorSekarangTruncated - $skorSebelumTruncated;

                // Round dulu ke 6 desimal untuk menghindari floating point error, baru truncate ke 1 desimal
                $scoreDiffRounded = round($scoreDiff, 6);
                $scoreDiffTruncated = floor($scoreDiffRounded * 10) / 10;

                // Determine status - Stagnan hanya jika benar-benar 0.0 setelah truncate
                $status = 'Stagnan';
                if ($scoreDiffTruncated > 0) {
                    $status = 'Naik';
                    $countNaik++;
                } elseif ($scoreDiffTruncated < 0) {
                    $status = 'Turun';
                    $countTurun++;
                } else {
                    $countStagnan++;
                }

                // Count category changes
                $previousCategoryRank = $getCategoryRank($previous->monev_status_kelengkapan);
                $currentCategoryRank = $getCategoryRank($current->monev_status_kelengkapan);

                if ($currentCategoryRank > $previousCategoryRank) {
                    $countKategoriNaik++;
                } elseif ($currentCategoryRank < $previousCategoryRank) {
                    $countKategoriTurun++;
                } else {
                    $countKategoriStagnan++;
                }

                $changes[] = [
                    'nama_instansi' => $current->nama_instansi,
                    'skor_sebelum' => $skorSebelumTruncated,
                    'skor_sekarang' => $skorSekarangTruncated,
                    'perubahan' => $scoreDiffTruncated,  // Gunakan nilai yang sudah di-truncate
                    'status' => $status,
                    // Kelengkapan data periode sebelumnya
                    'jumlah_asn_sebelum' => $previous->jumlah_asn ?? 0,
                    'sangat_lengkap_sebelum' => $previous->sangat_lengkap ?? 0,
                    'lengkap_sebelum' => $previous->lengkap ?? 0,
                    'cukup_lengkap_sebelum' => $previous->cukup_lengkap ?? 0,
                    'kurang_lengkap_sebelum' => $previous->kurang_lengkap ?? 0,
                    // Kelengkapan data periode sekarang
                    'jumlah_asn_sekarang' => $current->jumlah_asn ?? 0,
                    'sangat_lengkap_sekarang' => $current->sangat_lengkap ?? 0,
                    'lengkap_sekarang' => $current->lengkap ?? 0,
                    'cukup_lengkap_sekarang' => $current->cukup_lengkap ?? 0,
                    'kurang_lengkap_sekarang' => $current->kurang_lengkap ?? 0,
                    'status_kelengkapan_sekarang' => $current->monev_status_kelengkapan
                ];
            }
        }

        // Sort by change descending
        usort($changes, function($a, $b) {
            return $b['perubahan'] <=> $a['perubahan'];
        });

        // Calculate average skor instansi for both periods
        $avgSkorInstansiCurrent = $currentScores->avg('monev_skor_instansi');
        $avgSkorInstansiPrevious = $previousScores->avg('monev_skor_instansi');

        // Render HTML
        try {
            $html = view('dashboard.comparison-pdf', compact('changes', 'previousDate', 'currentDate', 'countNaik', 'countTurun', 'countStagnan', 'countKategoriNaik', 'countKategoriTurun', 'countKategoriStagnan', 'skorNasionalCurrent', 'skorNasionalPrevious', 'avgSkorInstansiCurrent', 'avgSkorInstansiPrevious'))->render();

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');

            $filename = 'Perbandingan_Periode_' . date('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Comparison Error: ' . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function exportComparisonKanregExcel(Request $request)
    {
        $previousDate = $request->input('previous_date');
        $currentDate = $request->input('current_date');

        if (!$previousDate || !$currentDate) {
            return back()->with('error', 'Periode perbandingan tidak lengkap');
        }

        // Get Kantor Regional statistics with comparison (SAMA SEPERTI DI comparePeriods)
        $kanregStats = DB::table('monev_dms_instansi_score as current')
            ->join('monev_dms_instansi_score as previous', function($join) use ($previousDate) {
                $join->on('current.id_instansi', '=', 'previous.id_instansi')
                     ->where('previous.upload_date', '=', $previousDate);
            })
            ->where('current.upload_date', $currentDate)
            ->select(
                DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END as kantor_regional_id'),
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(previous.monev_skor_instansi) as skor_sebelumnya'),
                DB::raw('AVG(current.monev_skor_instansi) as skor_sesudahnya'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi > 0 THEN 1 ELSE 0 END) as naik'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi < 0 THEN 1 ELSE 0 END) as turun'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi = 0 THEN 1 ELSE 0 END) as stagnan'),
                DB::raw('AVG(current.monev_skor_instansi - previous.monev_skor_instansi) as avg_perubahan')
            )
            ->groupBy(DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END'))
            ->orderBy(DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END'))
            ->get()
            ->map(function($stat) {
                // Set nama kanreg - semua kantor regional termasuk 00 adalah valid
                $stat->nama_kanreg = 'Kantor Regional ' . str_pad($stat->kantor_regional_id, 2, '0', STR_PAD_LEFT);

                // Round dulu ke 6 desimal untuk menghindari floating point error, baru truncate ke 1 desimal
                $perubahanRounded = round($stat->avg_perubahan, 6);
                $perubahanTruncated = floor($perubahanRounded * 10) / 10;

                // Tentukan status kanreg (Naik/Stagnan/Turun berdasarkan perubahan yang sudah di-truncate)
                if ($perubahanTruncated > 0) {
                    $stat->status = 'Naik';
                } elseif ($perubahanTruncated < 0) {
                    $stat->status = 'Turun';
                } else {
                    $stat->status = 'Stagnan';
                }

                return $stat;
            });

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'STATISTIK PER KANTOR REGIONAL - PERBANDINGAN PERIODE');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode Awal: ' . \Carbon\Carbon::parse($previousDate)->format('d F Y'));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Periode Akhir: ' . \Carbon\Carbon::parse($currentDate)->format('d F Y'));
        $sheet->mergeCells('A3:J3');
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A4:J4');

        // Table header
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Kantor Regional');
        $sheet->setCellValue('C6', 'Total Instansi');
        $sheet->setCellValue('D6', 'Skor Sebelumnya');
        $sheet->setCellValue('E6', 'Skor Sesudahnya');
        $sheet->setCellValue('F6', 'Naik');
        $sheet->setCellValue('G6', 'Stagnan');
        $sheet->setCellValue('H6', 'Turun');
        $sheet->setCellValue('I6', 'Rata-rata Perubahan');
        $sheet->setCellValue('J6', 'Status');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ];
        $sheet->getStyle('A6:J6')->applyFromArray($headerStyle);

        // Data rows
        $row = 7;
        $no = 1;
        foreach ($kanregStats as $stat) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $stat->nama_kanreg);
            $sheet->setCellValue('C' . $row, $stat->total_instansi);
            $sheet->setCellValue('D' . $row, number_format(floor($stat->skor_sebelumnya * 10) / 10, 1));
            $sheet->setCellValue('E' . $row, number_format(floor($stat->skor_sesudahnya * 10) / 10, 1));
            $sheet->setCellValue('F' . $row, $stat->naik);
            $sheet->setCellValue('G' . $row, $stat->stagnan);
            $sheet->setCellValue('H' . $row, $stat->turun);
            $sheet->setCellValue('I' . $row, number_format(floor($stat->avg_perubahan * 10) / 10, 1));
            $sheet->setCellValue('J' . $row, $stat->status);

            // Color code untuk Naik, Stagnan, Turun
            $sheet->getStyle('F' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF92D050');
            $sheet->getStyle('G' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF39C12');
            $sheet->getStyle('H' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFF6666');

            // Color code untuk Status
            if ($stat->status == 'Naik') {
                $sheet->getStyle('J' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF92D050');
            } elseif ($stat->status == 'Turun') {
                $sheet->getStyle('J' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFF6666');
            } else {
                $sheet->getStyle('J' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD3D3D3');
            }

            // Center align
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // Auto size columns
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(12);

        // Export
        $filename = 'Perbandingan_Kanreg_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function() use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportComparisonKanregPdf(Request $request)
    {
        $previousDate = $request->input('previous_date');
        $currentDate = $request->input('current_date');

        if (!$previousDate || !$currentDate) {
            return response()->json(['error' => 'Periode perbandingan tidak lengkap'], 404);
        }

        // Get Kantor Regional statistics with comparison (SAMA SEPERTI DI comparePeriods)
        $kanregStats = DB::table('monev_dms_instansi_score as current')
            ->join('monev_dms_instansi_score as previous', function($join) use ($previousDate) {
                $join->on('current.id_instansi', '=', 'previous.id_instansi')
                     ->where('previous.upload_date', '=', $previousDate);
            })
            ->where('current.upload_date', $currentDate)
            ->select(
                DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END as kantor_regional_id'),
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(previous.monev_skor_instansi) as skor_sebelumnya'),
                DB::raw('AVG(current.monev_skor_instansi) as skor_sesudahnya'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi > 0 THEN 1 ELSE 0 END) as naik'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi < 0 THEN 1 ELSE 0 END) as turun'),
                DB::raw('SUM(CASE WHEN current.monev_skor_instansi - previous.monev_skor_instansi = 0 THEN 1 ELSE 0 END) as stagnan'),
                DB::raw('AVG(current.monev_skor_instansi - previous.monev_skor_instansi) as avg_perubahan')
            )
            ->groupBy(DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END'))
            ->orderBy(DB::raw('CASE WHEN current.kantor_regional_id IS NULL THEN "0" WHEN current.kantor_regional_id = "\\N" THEN "0" WHEN current.kantor_regional_id = "\\\\N" THEN "0" ELSE current.kantor_regional_id END'))
            ->get()
            ->map(function($stat) {
                // Set nama kanreg - semua kantor regional termasuk 00 adalah valid
                $stat->nama_kanreg = 'Kantor Regional ' . str_pad($stat->kantor_regional_id, 2, '0', STR_PAD_LEFT);

                // Round dulu ke 6 desimal untuk menghindari floating point error, baru truncate ke 1 desimal
                $perubahanRounded = round($stat->avg_perubahan, 6);
                $perubahanTruncated = floor($perubahanRounded * 10) / 10;

                // Tentukan status kanreg (Naik/Stagnan/Turun berdasarkan perubahan yang sudah di-truncate)
                if ($perubahanTruncated > 0) {
                    $stat->status = 'Naik';
                } elseif ($perubahanTruncated < 0) {
                    $stat->status = 'Turun';
                } else {
                    $stat->status = 'Stagnan';
                }

                return $stat;
            });

        // Render HTML
        try {
            $html = view('dashboard.comparison-kanreg-pdf', compact('kanregStats', 'previousDate', 'currentDate'))->render();

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('a4', 'landscape'); // Landscape karena banyak kolom

            $filename = 'Perbandingan_Kanreg_' . date('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Kanreg Comparison Error: ' . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function filterMonevData(Request $request)
    {
        $selectedMonevDate = $request->input('monev_date');

        if (!$selectedMonevDate) {
            $monevNasionalScore = MonevDmsNasional::orderBy('upload_date', 'desc')->first();
        } else {
            $monevNasionalScore = MonevDmsNasional::where('upload_date', $selectedMonevDate)->first();
        }

        if (!$monevNasionalScore) {
            return response()->json(['error' => 'Data tidak ditemukan'], 404);
        }

        // Get top 5
        $monevTopInstansi = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date)
            ->orderBy('monev_skor_instansi', 'desc')
            ->limit(5)
            ->get();

        // Get bottom 5
        $monevBottomInstansi = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date)
            ->orderBy('monev_skor_instansi', 'asc')
            ->limit(5)
            ->get();

        // Get all instansi with pagination
        $monevAllInstansi = MonevDmsInstansiScore::where('upload_date', $monevNasionalScore->upload_date)
            ->orderBy('monev_skor_instansi', 'desc')
            ->paginate(10);

        // Get Kantor Regional statistics dengan COALESCE untuk handle NULL
        $monevKantorRegionalStats = DB::table('monev_dms_instansi_score')
            ->select(
                DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END as kantor_regional_id'),
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
            )
            ->where('upload_date', $monevNasionalScore->upload_date)
            ->groupBy(DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END'))
            ->orderBy(DB::raw('CASE WHEN kantor_regional_id IS NULL THEN "0" WHEN kantor_regional_id = "\\N" THEN "0" WHEN kantor_regional_id = "\\\\N" THEN "0" ELSE kantor_regional_id END'), 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'nasional_score' => $monevNasionalScore,
            'top_instansi' => $monevTopInstansi,
            'bottom_instansi' => $monevBottomInstansi,
            'all_instansi' => $monevAllInstansi,
            'kantor_regional_stats' => $monevKantorRegionalStats,
            'selected_date' => $monevNasionalScore->upload_date
        ]);
    }
}
