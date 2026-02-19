<?php

namespace App\Http\Controllers;

use App\Models\DmsUpload;
use App\Models\DmsPnsScore;
use App\Models\DmsInstansiScore;
use App\Jobs\ImportDmsCsvJob;
use App\Jobs\CalculateInstansiScoreJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DmsController extends Controller
{

    /**
     * Process CSV upload
     */
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:1024000', // Max 1GB (1000MB)
        ]);

        $file = $request->file('csv_file');
        $filename = 'dms_' . now()->format('Ymd_His') . '.csv';

        // Simpan langsung ke storage/app/dms-uploads (bukan pakai storeAs)
        $targetPath = storage_path('app/dms-uploads');
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0755, true);
        }
        $file->move($targetPath, $filename);

        // Create upload record
        $upload = DmsUpload::create([
            'filename' => $filename,
            'upload_date' => now(),
            'total_records' => 0,
            'processed_records' => 0,
            'status' => 'pending',
        ]);

        // Dispatch import job with full path
        $fullPath = storage_path('app/dms-uploads/' . $filename);
        ImportDmsCsvJob::dispatch($upload->id, $fullPath, now());

        return redirect()->route('dashboard.dms')
            ->with('success', 'Upload started! File is being processed in background. Refresh to see progress.');
    }

    /**
     * View upload details
     */
    public function show($uploadId)
    {
        $upload = DmsUpload::findOrFail($uploadId);

        // Get distinct instansi from this upload with counts
        // Join dms_pns_score_log dengan dms_pns untuk mendapat instansi
        $instansiList = DB::table('dms_pns_score_log')
            ->join('dms_pns', 'dms_pns_score_log.pns_id', '=', 'dms_pns.pns_id')
            ->where('dms_pns_score_log.upload_id', $uploadId)
            ->select('dms_pns.instansi_id', 'dms_pns.instansi_nama', DB::raw('COUNT(*) as total_pns'))
            ->groupBy('dms_pns.instansi_id', 'dms_pns.instansi_nama')
            ->orderBy('total_pns', 'desc')
            ->paginate(20);

        // Get LATEST calculation status for each instansi (karena bisa ada multiple history)
        $calculatedStatus = DmsInstansiScore::where('upload_id', $uploadId)
            ->orderBy('calculated_at', 'desc')
            ->get()
            ->groupBy('instansi_id')
            ->map(function ($items) {
                return $items->first(); // Ambil yang terbaru
            });

        foreach ($instansiList as $instansi) {
            $status = $calculatedStatus->get($instansi->instansi_id);
            $instansi->calculation_status = $status ? $status->calculation_status : 'pending';
            $instansi->skor_instansi_calculated_system = $status ? $status->skor_instansi_calculated_system : null;
            $instansi->skor_instansi_calculated_csv = $status ? $status->skor_instansi_calculated_csv : null;
            $instansi->status_kelengkapan = $status ? $status->status_kelengkapan : null;
        }

        return view('dashboard.show', compact('upload', 'instansiList'));
    }

    /**
     * Get upload progress (for AJAX polling)
     */
    public function progress($uploadId)
    {
        $upload = DmsUpload::findOrFail($uploadId);

        return response()->json([
            'status' => $upload->status,
            'processed' => $upload->processed_records,
            'total' => $upload->total_records,
            'percentage' => $upload->getProgressPercentage(),
        ]);
    }

    /**
     * Calculate score for specific instansi
     */
    public function calculateInstansi(Request $request)
    {
        $uploadId = $request->upload_id;
        $instansiId = $request->instansi_id;

        // Dispatch calculation job
        CalculateInstansiScoreJob::dispatch($uploadId, $instansiId);

        return response()->json([
            'success' => true,
            'message' => 'Calculation started for this instansi!'
        ]);
    }

    /**
     * Calculate ALL instansi for this upload
     */
    public function calculateAll($uploadId)
    {
        $instansiList = DB::table('dms_pns_scores')
            ->where('upload_id', $uploadId)
            ->select('instansi_id')
            ->distinct()
            ->get();

        foreach ($instansiList as $instansi) {
            CalculateInstansiScoreJob::dispatch($uploadId, $instansi->instansi_id);
        }

        return redirect()->back()
            ->with('success', count($instansiList) . ' calculations have been queued!');
    }

    /**
     * View instansi detail with scores
     */
    public function instansiDetail($uploadId, $instansiId)
    {
        $upload = DmsUpload::findOrFail($uploadId);

        // Get latest instansi score for this upload
        $instansiScore = DmsInstansiScore::where('upload_id', $uploadId)
            ->where('instansi_id', $instansiId)
            ->orderBy('calculated_at', 'desc')
            ->first();

        // Get PNS list with their scores from log
        $pnsList = DB::table('dms_pns_score_log')
            ->join('dms_pns', 'dms_pns_score_log.pns_id', '=', 'dms_pns.pns_id')
            ->where('dms_pns_score_log.upload_id', $uploadId)
            ->where('dms_pns.instansi_id', $instansiId)
            ->select(
                'dms_pns.nip',
                'dms_pns.nama',
                'dms_pns.status_cpns_pns',
                'dms_pns_score_log.skor_csv',
                'dms_pns_score_log.skor_calculated',
                'dms_pns_score_log.status_kelengkapan',
                'dms_pns_score_log.status_arsip'
            )
            ->orderBy('dms_pns.nama')
            ->paginate(50);

        return view('dashboard.instansi-detail', compact('upload', 'instansiScore', 'pnsList'));
    }

    /**
     * Show all instansi with latest scores (across all uploads)
     */
    public function allInstansi(Request $request)
    {
        $search = $request->input('search');

        // Get all instansi with their latest scores
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

        // Apply search filter
        if ($search) {
            $query->where('dis.instansi_nama', 'like', '%' . $search . '%');
        }

        $instansiList = $query->orderBy('dis.calculated_at', 'desc')
            ->paginate(20)
            ->appends(['search' => $search]);

        return view('dms.instansi-list', compact('instansiList', 'search'));
    }

    /**
     * Show detailed view of a specific instansi (across all uploads history)
     */
    public function instansiDetailFull($instansiId)
    {
        // Get instansi info from latest calculation
        $instansiInfo = DmsInstansiScore::where('instansi_id', $instansiId)
            ->where('calculation_status', 'completed')
            ->orderBy('calculated_at', 'desc')
            ->firstOrFail();

        // Get score history (all uploads for this instansi)
        $scoreHistory = DmsInstansiScore::where('instansi_id', $instansiId)
            ->where('calculation_status', 'completed')
            ->join('dms_uploads', 'dms_instansi_scores.upload_id', '=', 'dms_uploads.id')
            ->select(
                'dms_instansi_scores.*',
                'dms_uploads.upload_date'
            )
            ->orderBy('dms_uploads.upload_date', 'asc')
            ->get();

        // Get latest PNS list with their scores
        $latestUploadId = $scoreHistory->last()->upload_id;

        $pnsList = DB::table('dms_pns_score_log')
            ->join('dms_pns', 'dms_pns_score_log.pns_id', '=', 'dms_pns.pns_id')
            ->where('dms_pns_score_log.upload_id', $latestUploadId)
            ->where('dms_pns.instansi_id', $instansiId)
            ->select(
                'dms_pns.nip',
                'dms_pns.nama',
                'dms_pns.status_cpns_pns',
                'dms_pns_score_log.skor_csv',
                'dms_pns_score_log.skor_calculated',
                'dms_pns_score_log.status_kelengkapan',
                'dms_pns_score_log.status_arsip'
            )
            ->orderBy('dms_pns_score_log.skor_calculated', 'desc')
            ->paginate(50);

        // Prepare chart data
        $chartData = [
            'labels' => $scoreHistory->map(function($item) {
                return \Carbon\Carbon::parse($item->upload_date)->format('d M Y');
            })->toArray(),
            'system_scores' => $scoreHistory->pluck('skor_instansi_calculated_system')->toArray(),
            'csv_scores' => $scoreHistory->pluck('skor_instansi_calculated_csv')->toArray(),
        ];

        // Get score distribution for pie chart (dari data terbaru)
        $scoreDistribution = DB::table('dms_pns_score_log')
            ->join('dms_pns', 'dms_pns_score_log.pns_id', '=', 'dms_pns.pns_id')
            ->where('dms_pns_score_log.upload_id', $latestUploadId)
            ->where('dms_pns.instansi_id', $instansiId)
            ->select('status_kelengkapan', DB::raw('COUNT(*) as jumlah'))
            ->whereNotNull('status_kelengkapan')
            ->groupBy('status_kelengkapan')
            ->get()
            ->keyBy('status_kelengkapan');

        return view('dms.instansi-detail-full', compact('instansiInfo', 'scoreHistory', 'pnsList', 'chartData', 'scoreDistribution'));
    }
}