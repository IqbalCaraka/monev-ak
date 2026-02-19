<?php

namespace App\Jobs;

use App\Models\DmsPns;
use App\Models\DmsPnsScoreLog;
use App\Models\DmsInstansiScore;
use App\Models\DmsUpload;
use App\Services\DmsScoreCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CalculateInstansiScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    protected $uploadId;
    protected $instansiId;

    public function __construct($uploadId, $instansiId)
    {
        $this->uploadId = $uploadId;
        $this->instansiId = $instansiId;
    }

    public function handle()
    {
        $upload = DmsUpload::find($this->uploadId);

        // Get all score logs for this instansi from this upload
        // Join dengan dms_pns untuk filter by instansi_id
        $scoreLogs = DB::table('dms_pns_score_log')
            ->join('dms_pns', 'dms_pns_score_log.pns_id', '=', 'dms_pns.pns_id')
            ->where('dms_pns_score_log.upload_id', $this->uploadId)
            ->where('dms_pns.instansi_id', $this->instansiId)
            ->select(
                'dms_pns_score_log.*',
                'dms_pns.instansi_nama'
            )
            ->get();

        if ($scoreLogs->isEmpty()) {
            return;
        }

        // Calculate aggregates
        $scoresCalculated = $scoreLogs->pluck('skor_calculated')->filter()->toArray();
        $scoresCsv = $scoreLogs->pluck('skor_csv')->filter()->toArray();

        $avgCalculatedSystem = !empty($scoresCalculated)
            ? round(array_sum($scoresCalculated) / count($scoresCalculated), 2)
            : null;

        $avgCalculatedCsv = !empty($scoresCsv)
            ? round(array_sum($scoresCsv) / count($scoresCsv), 2)
            : null;

        $minCalculated = !empty($scoresCalculated)
            ? round(min($scoresCalculated), 2)
            : null;

        $maxCalculated = !empty($scoresCalculated)
            ? round(max($scoresCalculated), 2)
            : null;

        // Calculate score distribution (berdasarkan skor_calculated)
        $count_80_100 = 0;
        $count_60_79 = 0;
        $count_40_59 = 0;
        $count_0_39 = 0;

        foreach ($scoresCalculated as $skor) {
            if ($skor >= 80) {
                $count_80_100++;
            } elseif ($skor >= 60) {
                $count_60_79++;
            } elseif ($skor >= 40) {
                $count_40_59++;
            } else {
                $count_0_39++;
            }
        }

        // Tentukan status kelengkapan instansi (berdasarkan avg skor_csv)
        $statusKelengkapan = DmsScoreCalculator::tentukanStatusKelengkapan($avgCalculatedCsv);

        // INSERT NEW RECORD (bukan update) - untuk history/log
        DmsInstansiScore::create([
            'upload_id' => $this->uploadId,
            'instansi_id' => $this->instansiId,
            'instansi_nama' => $scoreLogs->first()->instansi_nama,
            'upload_date' => $upload->upload_date,
            'total_pns' => $scoreLogs->count(),
            'skor_instansi_calculated_system' => $avgCalculatedSystem,
            'skor_instansi_calculated_csv' => $avgCalculatedCsv,
            'min_skor_calculated' => $minCalculated,
            'max_skor_calculated' => $maxCalculated,
            'count_80_100' => $count_80_100,
            'count_60_79' => $count_60_79,
            'count_40_59' => $count_40_59,
            'count_0_39' => $count_0_39,
            'status_kelengkapan' => $statusKelengkapan,
            'calculation_status' => 'completed',
            'calculated_at' => now(),
        ]);
    }
}