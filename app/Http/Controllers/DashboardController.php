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
        } else {
            $monevAllInstansi = collect();
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
            'monevKantorRegionalStats'
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
}
