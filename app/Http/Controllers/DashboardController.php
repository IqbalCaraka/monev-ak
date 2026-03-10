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
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Kantor Regional: ' . $kanregId);
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('A3', 'Periode Penilaian: ' . \Carbon\Carbon::parse($monevDate)->format('d F Y'));
        $sheet->mergeCells('A3:E3');

        $sheet->setCellValue('A4', 'Waktu Cetak: ' . \Carbon\Carbon::now()->format('d F Y, H:i') . ' WIB');
        $sheet->mergeCells('A4:E4');

        // Table header
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Nama Instansi');
        $sheet->setCellValue('C6', 'ID Instansi');
        $sheet->setCellValue('D6', 'Skor');
        $sheet->setCellValue('E6', 'Status Kelengkapan');

        $sheet->getStyle('A6:E6')->getFont()->setBold(true);
        $sheet->getStyle('A6:E6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');

        // Data
        $row = 7;
        $no = 1;
        foreach ($instansiList as $instansi) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $instansi->nama_instansi);
            $sheet->setCellValue('C' . $row, $instansi->id_instansi);
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
}
