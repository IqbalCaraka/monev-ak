<?php

namespace App\Jobs;

use App\Models\DmsUpload;
use App\Models\DmsPns;
use App\Models\DmsPnsScoreLog;
use App\Services\DmsScoreCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Exception;

class ImportDmsCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    public $tries = 3;

    protected $uploadId;
    protected $filePath;
    protected $uploadDate;

    public function __construct($uploadId, $filePath, $uploadDate)
    {
        $this->uploadId = $uploadId;
        $this->filePath = $filePath;
        $this->uploadDate = $uploadDate;
    }

    public function handle()
    {
        try {
            $upload = DmsUpload::find($this->uploadId);
            $upload->update(['status' => 'processing']);

            $file = fopen($this->filePath, 'r');

            // Read header
            $header = fgetcsv($file);

            $dmsPnsChunk = [];
            $scoreLogChunk = [];
            $chunkSize = 2000; // Increased for better performance
            $totalProcessed = 0;

            while (($row = fgetcsv($file)) !== false) {
                // Combine header with row data
                $data = array_combine($header, $row);

                // Skip jika data tidak lengkap
                if (empty($data['nip']) || empty($data['status_arsip']) || $data['status_arsip'] === 'null') {
                    continue;
                }

                // Parse status_arsip JSON
                $statusArsip = json_decode($data['status_arsip'], true);
                if (!$statusArsip) {
                    continue; // Skip jika JSON invalid
                }

                // 1. Collect data untuk BATCH UPSERT ke dms_pns (Master PNS)
                $dmsPnsChunk[$data['nip']] = [
                    'pns_id' => $data['id'],
                    'nip' => $data['nip'],
                    'nama' => $data['nama'],
                    'status_cpns_pns' => $data['status_cpns_pns'],
                    'instansi_id' => $data['instansi_induk_id'],
                    'instansi_nama' => $data['instansi_nama'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // 2. HITUNG SKOR dari status_arsip JSON
                $skorResult = DmsScoreCalculator::hitungSkor($statusArsip, $data['status_cpns_pns']);

                // 3. Prepare data untuk INSERT ke dms_pns_score_log
                $skorCsv = !empty($data['skor_arsip_2026']) ? (float)$data['skor_arsip_2026'] : null;

                // 4. Tentukan status kelengkapan berdasarkan skor_csv
                $statusKelengkapan = DmsScoreCalculator::tentukanStatusKelengkapan($skorCsv);

                $scoreLogChunk[] = [
                    'upload_id' => $this->uploadId,
                    'pns_id' => $data['id'], // UUID dari CSV
                    'status_arsip' => $data['status_arsip'], // Keep as JSON string
                    'skor_csv' => $skorCsv,
                    'skor_calculated' => $skorResult['skor_final'],
                    'status_kelengkapan' => $statusKelengkapan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Batch insert when chunk is full
                if (count($scoreLogChunk) >= $chunkSize) {
                    // BATCH UPSERT dms_pns
                    DmsPns::upsert(
                        array_values($dmsPnsChunk),
                        ['nip'], // unique key
                        ['pns_id', 'nama', 'status_cpns_pns', 'instansi_id', 'instansi_nama', 'updated_at']
                    );

                    // BATCH INSERT dms_pns_score_log
                    DB::table('dms_pns_score_log')->insert($scoreLogChunk);

                    $totalProcessed += count($scoreLogChunk);

                    // Update progress
                    $upload->increment('processed_records', count($scoreLogChunk));

                    $dmsPnsChunk = [];
                    $scoreLogChunk = [];
                }
            }

            // Insert remaining rows
            if (!empty($scoreLogChunk)) {
                // BATCH UPSERT dms_pns
                DmsPns::upsert(
                    array_values($dmsPnsChunk),
                    ['nip'],
                    ['pns_id', 'nama', 'status_cpns_pns', 'instansi_id', 'instansi_nama', 'updated_at']
                );

                // BATCH INSERT dms_pns_score_log
                DB::table('dms_pns_score_log')->insert($scoreLogChunk);
                $totalProcessed += count($scoreLogChunk);
                $upload->increment('processed_records', count($scoreLogChunk));
            }

            fclose($file);

            // Update upload status
            $upload->update([
                'status' => 'completed',
                'total_records' => $totalProcessed,
            ]);

            // AUTO DISPATCH CALCULATE JOBS untuk semua instansi
            $this->autoDispatchCalculateJobs();

        } catch (Exception $e) {
            $upload = DmsUpload::find($this->uploadId);
            $upload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Auto dispatch calculate jobs untuk semua instansi dari upload ini
     */
    protected function autoDispatchCalculateJobs()
    {
        // Get distinct instansi IDs from this upload
        $instansiIds = DB::table('dms_pns_score_log')
            ->join('dms_pns', 'dms_pns_score_log.pns_id', '=', 'dms_pns.pns_id')
            ->where('dms_pns_score_log.upload_id', $this->uploadId)
            ->distinct()
            ->pluck('dms_pns.instansi_id');

        // Dispatch calculate job untuk setiap instansi
        foreach ($instansiIds as $instansiId) {
            \App\Jobs\CalculateInstansiScoreJob::dispatch($this->uploadId, $instansiId);
        }

        // Dispatch national score calculation job after all instansi jobs
        // This will calculate the national average after all instansi calculations are complete
        \App\Jobs\CalculateNasionalScoreJob::dispatch($this->uploadId)
            ->delay(now()->addMinutes(2)); // Add delay to ensure instansi jobs finish first
    }
}