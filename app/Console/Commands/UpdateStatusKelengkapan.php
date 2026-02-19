<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\DmsScoreCalculator;
use App\Models\DmsPnsScoreLog;
use App\Models\DmsInstansiScore;

class UpdateStatusKelengkapan extends Command
{
    protected $signature = 'dms:update-status-kelengkapan';
    protected $description = 'Update status_kelengkapan untuk data yang sudah ada di dms_pns_score_log dan dms_instansi_scores';

    public function handle()
    {
        $this->info('Updating status_kelengkapan for existing data...');

        // 1. Update dms_pns_score_log
        $this->info('Updating dms_pns_score_log...');

        $scoreLogCount = 0;
        DmsPnsScoreLog::chunk(1000, function ($scoreLogs) use (&$scoreLogCount) {
            foreach ($scoreLogs as $scoreLog) {
                $statusKelengkapan = DmsScoreCalculator::tentukanStatusKelengkapan($scoreLog->skor_csv);
                $scoreLog->update(['status_kelengkapan' => $statusKelengkapan]);
                $scoreLogCount++;
            }
            $this->info("Processed {$scoreLogCount} PNS score logs...");
        });

        $this->info("✓ Updated {$scoreLogCount} records in dms_pns_score_log");

        // 2. Update dms_instansi_scores
        $this->info('Updating dms_instansi_scores...');

        $instansiCount = 0;
        DmsInstansiScore::chunk(100, function ($instansiScores) use (&$instansiCount) {
            foreach ($instansiScores as $instansiScore) {
                // Hitung ulang distribusi skor
                $scoreLogs = DB::table('dms_pns_score_log')
                    ->join('dms_pns', 'dms_pns_score_log.pns_id', '=', 'dms_pns.pns_id')
                    ->where('dms_pns_score_log.upload_id', $instansiScore->upload_id)
                    ->where('dms_pns.instansi_id', $instansiScore->instansi_id)
                    ->pluck('dms_pns_score_log.skor_calculated')
                    ->filter()
                    ->toArray();

                $count_80_100 = 0;
                $count_60_79 = 0;
                $count_40_59 = 0;
                $count_0_39 = 0;

                foreach ($scoreLogs as $skor) {
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

                // Tentukan status kelengkapan berdasarkan avg skor_csv
                $statusKelengkapan = DmsScoreCalculator::tentukanStatusKelengkapan($instansiScore->skor_instansi_calculated_csv);

                // Update
                $instansiScore->update([
                    'count_80_100' => $count_80_100,
                    'count_60_79' => $count_60_79,
                    'count_40_59' => $count_40_59,
                    'count_0_39' => $count_0_39,
                    'status_kelengkapan' => $statusKelengkapan,
                ]);

                $instansiCount++;
            }
        });

        $this->info("✓ Updated {$instansiCount} records in dms_instansi_scores");

        $this->info('');
        $this->info('✓ All done! Status kelengkapan updated successfully.');

        return 0;
    }
}
