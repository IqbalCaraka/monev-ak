<?php

namespace App\Jobs;

use App\Models\DmsNasional;
use App\Models\DmsUpload;
use App\Models\DmsInstansiScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CalculateNasionalScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    protected $uploadId;

    public function __construct($uploadId)
    {
        $this->uploadId = $uploadId;
    }

    public function handle()
    {
        $upload = DmsUpload::find($this->uploadId);

        if (!$upload) {
            return;
        }

        // Get all completed instansi scores for this upload
        $instansiScores = DmsInstansiScore::where('upload_id', $this->uploadId)
            ->where('calculation_status', 'completed')
            ->get();

        if ($instansiScores->isEmpty()) {
            return;
        }

        // Calculate averages from instansi scores
        $avgSkorSystem = $instansiScores->avg('skor_instansi_calculated_system');
        $avgSkorCsv = $instansiScores->avg('skor_instansi_calculated_csv');
        $minSkor = $instansiScores->min('skor_instansi_calculated_system');
        $maxSkor = $instansiScores->max('skor_instansi_calculated_system');

        // Count distribution from dms_pns_score_log
        $distribution = DB::table('dms_pns_score_log')
            ->where('upload_id', $this->uploadId)
            ->select(
                DB::raw('COUNT(CASE WHEN status_kelengkapan = "Sangat Lengkap" THEN 1 END) as sangat_lengkap'),
                DB::raw('COUNT(CASE WHEN status_kelengkapan = "Lengkap" THEN 1 END) as lengkap'),
                DB::raw('COUNT(CASE WHEN status_kelengkapan = "Cukup Lengkap" THEN 1 END) as cukup_lengkap'),
                DB::raw('COUNT(CASE WHEN status_kelengkapan = "Kurang Lengkap" THEN 1 END) as kurang_lengkap'),
                DB::raw('COUNT(*) as total_pns')
            )
            ->first();

        // Create or update nasional score
        DmsNasional::updateOrCreate(
            ['upload_id' => $this->uploadId],
            [
                'upload_date' => $upload->upload_date,
                'total_instansi' => $instansiScores->count(),
                'total_pns' => $distribution->total_pns,
                'avg_skor_nasional_system' => round($avgSkorSystem, 2),
                'avg_skor_nasional_csv' => round($avgSkorCsv, 2),
                'min_skor_instansi' => round($minSkor, 2),
                'max_skor_instansi' => round($maxSkor, 2),
                'count_sangat_lengkap' => $distribution->sangat_lengkap,
                'count_lengkap' => $distribution->lengkap,
                'count_cukup_lengkap' => $distribution->cukup_lengkap,
                'count_kurang_lengkap' => $distribution->kurang_lengkap,
                'calculation_status' => 'completed',
                'calculated_at' => now(),
            ]
        );
    }
}
