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

            // Get Kantor Regional statistics
            $monevKantorRegionalStats = DB::table('monev_dms_instansi_score')
                ->select(
                    'kantor_regional_id',
                    DB::raw('COUNT(*) as total_instansi'),
                    DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                    DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
                )
                ->where('upload_date', $monevNasionalScore->upload_date)
                ->whereNotNull('kantor_regional_id')
                ->groupBy('kantor_regional_id')
                ->orderByDesc('rata_rata_skor')
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
                            $scoreDiff = $current->monev_skor_instansi - $previous->monev_skor_instansi;

                            $changes[] = [
                                'id_instansi' => $idInstansi,
                                'nama_instansi' => $current->nama_instansi,
                                'skor_sebelum' => $previous->monev_skor_instansi,
                                'skor_sekarang' => $current->monev_skor_instansi,
                                'perubahan' => $scoreDiff,
                                'perubahan_abs' => abs($scoreDiff)
                            ];

                            // Count trends
                            if ($scoreDiff > 0.5) {
                                $countNaik++;
                            } elseif ($scoreDiff < -0.5) {
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
            'comparisonData'
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

        // Get Kantor Regional statistics
        $monevKantorRegionalStats = DB::table('monev_dms_instansi_score')
            ->select(
                'kantor_regional_id',
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
            )
            ->where('upload_date', $monevNasionalScore->upload_date)
            ->whereNotNull('kantor_regional_id')
            ->groupBy('kantor_regional_id')
            ->orderByDesc('rata_rata_skor')
            ->get();

        // Generate PDF
        $pdf = \PDF::loadView('dashboard.monev-pdf', compact(
            'monevNasionalScore',
            'monevTopInstansi',
            'monevBottomInstansi',
            'monevKantorRegionalStats'
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
                $scoreDiff = $current->monev_skor_instansi - $previous->monev_skor_instansi;

                $changes[] = [
                    'id_instansi' => $idInstansi,
                    'nama_instansi' => $current->nama_instansi,
                    'skor_sebelum' => $previous->monev_skor_instansi,
                    'skor_sekarang' => $current->monev_skor_instansi,
                    'perubahan' => $scoreDiff,
                    'perubahan_abs' => abs($scoreDiff)
                ];

                // Count trends
                if ($scoreDiff > 0.5) {
                    $countNaik++;
                } elseif ($scoreDiff < -0.5) {
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
            'all_comparisons' => $changes,
            'count_naik' => $countNaik,
            'count_turun' => $countTurun,
            'count_stagnan' => $countStagnan,
            'total_compared' => count($changes)
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
            $sheet->setCellValue('C' . $row, number_format($instansi->monev_skor_instansi, 2));
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

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header info
        $sheet->setCellValue('A1', 'DAFTAR SEMUA INSTANSI - MONEV DMS');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode Penilaian: ' . \Carbon\Carbon::parse($monevDate)->format('d F Y'));
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A3:E3');

        $sheet->setCellValue('A4', 'Total Instansi: ' . $instansiList->count());
        $sheet->mergeCells('A4:E4');
        $sheet->getStyle('A4')->getFont()->setBold(true);

        // Table header
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Nama Instansi');
        $sheet->setCellValue('C6', 'Kantor Regional');
        $sheet->setCellValue('D6', 'Skor');
        $sheet->setCellValue('E6', 'Status Kelengkapan');

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
        $sheet->getStyle('A6:E6')->applyFromArray($headerStyle);

        // Data rows
        $row = 7;
        $no = 1;
        foreach ($instansiList as $instansi) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $instansi->nama_instansi);
            $sheet->setCellValue('C' . $row, 'Kanreg ' . $instansi->kantor_regional_id);
            $sheet->setCellValue('D' . $row, number_format($instansi->monev_skor_instansi, 2));
            $sheet->setCellValue('E' . $row, $instansi->monev_status_kelengkapan);

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
                $sheet->getStyle('E' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($statusColor);
            }

            // Center align for specific columns
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // Auto size columns
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(20);

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

        // Render HTML first
        try {
            $html = view('dashboard.all-instansi-pdf', compact('instansiList', 'monevDate'))->render();

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

        // Get Kantor Regional statistics
        $kanregStats = DB::table('monev_dms_instansi_score')
            ->select(
                'kantor_regional_id',
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
            )
            ->where('upload_date', $monevDate)
            ->groupBy('kantor_regional_id')
            ->orderBy('kantor_regional_id')
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
            $sheet->setCellValue('B' . $row, 'Kanreg ' . $kanreg->kantor_regional_id);
            $sheet->setCellValue('C' . $row, $kanreg->total_instansi);
            $sheet->setCellValue('D' . $row, number_format($kanreg->rata_rata_skor, 2));
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

        // Get Kantor Regional statistics
        $kanregStats = DB::table('monev_dms_instansi_score')
            ->select(
                'kantor_regional_id',
                DB::raw('COUNT(*) as total_instansi'),
                DB::raw('AVG(monev_skor_instansi) as rata_rata_skor'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Sangat Lengkap" THEN 1 END) as count_sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Lengkap" THEN 1 END) as count_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Cukup Lengkap" THEN 1 END) as count_cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN monev_status_kelengkapan = "Kurang Lengkap" THEN 1 END) as count_kurang_lengkap')
            )
            ->where('upload_date', $monevDate)
            ->groupBy('kantor_regional_id')
            ->orderBy('kantor_regional_id')
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

        // Get scores from both periods
        $currentScores = MonevDmsInstansiScore::where('upload_date', $currentDate)
            ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi')
            ->get()
            ->keyBy('id_instansi');

        $previousScores = MonevDmsInstansiScore::where('upload_date', $previousDate)
            ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi')
            ->get()
            ->keyBy('id_instansi');

        // Calculate changes
        $changes = [];
        foreach ($currentScores as $idInstansi => $current) {
            if (isset($previousScores[$idInstansi])) {
                $previous = $previousScores[$idInstansi];
                $scoreDiff = $current->monev_skor_instansi - $previous->monev_skor_instansi;

                // Determine status
                $status = 'Stagnan';
                if ($scoreDiff > 0.5) {
                    $status = 'Naik';
                } elseif ($scoreDiff < -0.5) {
                    $status = 'Turun';
                }

                $changes[] = [
                    'nama_instansi' => $current->nama_instansi,
                    'skor_sebelum' => $previous->monev_skor_instansi,
                    'skor_sekarang' => $current->monev_skor_instansi,
                    'perubahan' => $scoreDiff,
                    'status' => $status
                ];
            }
        }

        // Sort by change descending
        usort($changes, function($a, $b) {
            return $b['perubahan'] <=> $a['perubahan'];
        });

        // Create Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header info
        $sheet->setCellValue('A1', 'ANALISIS PERBANDINGAN PERIODE - MONEV DMS');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode Awal: ' . \Carbon\Carbon::parse($previousDate)->format('d F Y'));
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Periode Akhir: ' . \Carbon\Carbon::parse($currentDate)->format('d F Y'));
        $sheet->mergeCells('A3:F3');
        $sheet->getStyle('A3')->getFont()->setBold(true);

        $sheet->setCellValue('A4', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A4:F4');

        $sheet->setCellValue('A5', 'Total Instansi: ' . count($changes));
        $sheet->mergeCells('A5:F5');
        $sheet->getStyle('A5')->getFont()->setBold(true);

        // Table header
        $sheet->setCellValue('A7', 'No');
        $sheet->setCellValue('B7', 'Nama Instansi');
        $sheet->setCellValue('C7', \Carbon\Carbon::parse($previousDate)->format('d M Y'));
        $sheet->setCellValue('D7', \Carbon\Carbon::parse($currentDate)->format('d M Y'));
        $sheet->setCellValue('E7', 'Perubahan');
        $sheet->setCellValue('F7', 'Status');

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
        $sheet->getStyle('A7:F7')->applyFromArray($headerStyle);

        // Data rows
        $row = 8;
        $no = 1;
        foreach ($changes as $change) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $change['nama_instansi']);
            $sheet->setCellValue('C' . $row, number_format($change['skor_sebelum'], 2));
            $sheet->setCellValue('D' . $row, number_format($change['skor_sekarang'], 2));
            $sheet->setCellValue('E' . $row, number_format($change['perubahan'], 2));
            $sheet->setCellValue('F' . $row, $change['status']);

            // Color code status
            $statusColor = match($change['status']) {
                'Naik' => 'FF92D050',
                'Turun' => 'FFFF6666',
                'Stagnan' => 'FFF39C12',
                default => 'FFCCCCCC'
            };

            $sheet->getStyle('F' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($statusColor);

            // Center align
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // Auto size columns
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);

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

        // Get scores from both periods
        $currentScores = MonevDmsInstansiScore::where('upload_date', $currentDate)
            ->select('id_instansi', 'nama_instansi', 'monev_skor_instansi')
            ->get()
            ->keyBy('id_instansi');

        $previousScores = MonevDmsInstansiScore::where('upload_date', $previousDate)
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
                $scoreDiff = $current->monev_skor_instansi - $previous->monev_skor_instansi;

                // Determine status
                $status = 'Stagnan';
                if ($scoreDiff > 0.5) {
                    $status = 'Naik';
                    $countNaik++;
                } elseif ($scoreDiff < -0.5) {
                    $status = 'Turun';
                    $countTurun++;
                } else {
                    $countStagnan++;
                }

                $changes[] = [
                    'nama_instansi' => $current->nama_instansi,
                    'skor_sebelum' => $previous->monev_skor_instansi,
                    'skor_sekarang' => $current->monev_skor_instansi,
                    'perubahan' => $scoreDiff,
                    'status' => $status
                ];
            }
        }

        // Sort by change descending
        usort($changes, function($a, $b) {
            return $b['perubahan'] <=> $a['perubahan'];
        });

        // Render HTML
        try {
            $html = view('dashboard.comparison-pdf', compact('changes', 'previousDate', 'currentDate', 'countNaik', 'countTurun', 'countStagnan'))->render();

            $pdf = \PDF::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');

            $filename = 'Perbandingan_Periode_' . date('Ymd') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Exception $e) {
            \Log::error('PDF Comparison Error: ' . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed: ' . $e->getMessage()], 500);
        }
    }
}
