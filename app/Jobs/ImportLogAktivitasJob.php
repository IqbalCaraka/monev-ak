<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ImportLogAktivitasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour
    public $tries = 3;

    protected $filePath;
    protected $fileName;

    public function __construct($filePath, $fileName)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function handle()
    {
        try {
            Log::info("Starting log aktivitas import: {$this->fileName}");

            // Get all valid NIPs from pegawai table
            $validNips = DB::table('pegawai')->pluck('nip')->toArray();
            $handle = fopen($this->filePath, 'r');

            if (!$handle) {
                throw new Exception("Cannot open file: {$this->filePath}");
            }

            // Skip header
            fgetcsv($handle);

            $batchMain = [];
            $batchStaging = [];
            $countMain = 0;
            $countStaging = 0;
            $batchSize = 1000;
            $allAffectedNips = [];

            while (($data = fgetcsv($handle)) !== false) {
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
                        $createdAt = Carbon::parse(trim($data[7]));
                        $dayName = $this->getDayNameFromDate($createdAt);
                        $workCategory = $this->getWorkCategoryFromDay($dayName);
                    } catch (\Exception $e) {
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
                    $allAffectedNips[$nip] = true;
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

            // Regenerate summary for ALL affected NIPs
            if ($countMain > 0 && !empty($allAffectedNips)) {
                $affectedNips = array_keys($allAffectedNips);

                // Hapus summary lama untuk NIP yang terpengaruh
                DB::table('pegawai_aktivitas_summary')
                    ->whereIn('nip', $affectedNips)
                    ->delete();

                // Regenerate summary untuk SEMUA NIP yang terpengaruh sekaligus
                $sql = "
                    INSERT INTO pegawai_aktivitas_summary (nip, kategori_aktivitas, total_aktivitas, last_activity_at, created_at, updated_at)
                    SELECT
                        created_by_nip,
                        CASE
                            WHEN event_name = 'unggah_dokumen' AND details != 'unggah_dokumen'
                                THEN 'Inject - Unggah Dokumen'
                            WHEN event_name = 'unggah_dokumen' AND details = 'unggah_dokumen'
                                THEN 'Unggah Dokumen'
                            WHEN event_name = 'mapping_dokumen' AND (details NOT LIKE '%inject%' OR details IS NULL)
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
                        AND NOT (event_name = 'mapping_dokumen' AND details LIKE '%inject%')
                    GROUP BY created_by_nip, kategori_aktivitas
                ";

                DB::statement($sql, $affectedNips);
            }

            Log::info("Log aktivitas import completed: {$countMain} main, {$countStaging} staging");

        } catch (Exception $e) {
            Log::error("Log aktivitas import failed: " . $e->getMessage());
            throw $e;
        }
    }

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

    private function getDayNameFromDate($date): string
    {
        $dayMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];

        return $dayMap[$date->format('l')] ?? 'Unknown';
    }

    private function getWorkCategoryFromDay(string $dayName): string
    {
        // Sabtu-Minggu = Libur
        if (in_array($dayName, ['Sabtu', 'Minggu'])) {
            return 'Libur';
        }

        // Senin-Rabu = WFA
        if (in_array($dayName, ['Senin', 'Rabu'])) {
            return 'WFA';
        }

        // Selasa-Kamis-Jumat = WFO
        if (in_array($dayName, ['Selasa', 'Kamis', 'Jumat'])) {
            return 'WFO';
        }

        return 'Unknown';
    }
}
