<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogAktivitasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/data/log_activity inject.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("File not found: {$csvFile}");
            return;
        }

        $this->command->info("Starting import from: {$csvFile}");

        // STEP 1: Import raw data ke log_aktivitas
        $this->importLogActivities($csvFile);

        // STEP 2: Generate summary table
        $this->generateSummary();

        $this->command->info("✓ Log aktivitas import completed!");
    }

    /**
     * Import data CSV ke table log_aktivitas dengan batch processing
     * SKIP records yang NIP-nya tidak ada di table pegawai
     */
    private function importLogActivities(string $csvFile): void
    {
        // Get all valid NIPs from pegawai table
        $validNips = DB::table('pegawai')->pluck('nip')->toArray();
        $this->command->info("  → Found " . count($validNips) . " valid NIPs in pegawai table");

        $file = fopen($csvFile, 'r');
        $header = true;
        $batchMain = [];
        $batchStaging = [];
        $countMain = 0;
        $countStaging = 0;
        $batchSize = 1000;

        $this->command->info("Importing log activities...");

        while (($data = fgetcsv($file)) !== false) {
            // Skip header row
            if ($header) {
                $header = false;
                continue;
            }

            // Validasi minimal kolom
            if (count($data) < 9) {
                continue;
            }

            // Clean BOM dari ID
            $id = str_replace("\xEF\xBB\xBF", '', trim($data[0]));

            if (empty($id)) {
                continue;
            }

            // Get NIP dari CSV
            $nip = !empty($data[6]) ? trim($data[6]) : null;

            // SKIP jika NIP kosong
            if (empty($nip)) {
                continue;
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
            ];

            // Pisahkan: NIP ada di pegawai -> main table, tidak ada -> staging
            if (in_array($nip, $validNips)) {
                $batchMain[] = $record;
            } else {
                $batchStaging[] = $record;
            }

            // Insert batch main table setiap 1000 records
            if (count($batchMain) >= $batchSize) {
                $this->insertIgnore($batchMain, 'log_aktivitas');
                $countMain += count($batchMain);
                $this->command->info("  ✓ Main: {$countMain} | Staging: {$countStaging}");
                $batchMain = [];
            }

            // Insert batch staging table setiap 1000 records
            if (count($batchStaging) >= $batchSize) {
                $this->insertIgnore($batchStaging, 'log_aktivitas_staging');
                $countStaging += count($batchStaging);
                $this->command->warn("  ⏳ Main: {$countMain} | Staging: {$countStaging}");
                $batchStaging = [];
            }
        }

        // Insert sisa batch
        if (!empty($batchMain)) {
            $this->insertIgnore($batchMain, 'log_aktivitas');
            $countMain += count($batchMain);
        }

        if (!empty($batchStaging)) {
            $this->insertIgnore($batchStaging, 'log_aktivitas_staging');
            $countStaging += count($batchStaging);
        }

        fclose($file);

        $this->command->info("  ✓ Total {$countMain} log activities imported to main table!");
        $this->command->warn("  ⏳ Total {$countStaging} logs moved to staging (NIP not found in pegawai table)");
    }

    /**
     * Insert data dengan INSERT IGNORE untuk skip duplicate ID
     */
    private function insertIgnore(array $data, string $table = 'log_aktivitas'): void
    {
        if (empty($data)) {
            return;
        }

        // Buat placeholder untuk INSERT IGNORE
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

        // Execute INSERT IGNORE query
        DB::statement("INSERT IGNORE INTO {$table} ({$columnList}) VALUES {$valuesList}", $bindings);
    }

    /**
     * Generate summary table dengan business logic:
     *
     * INJECT (Parent dengan 2 sub):
     * - Inject - Unggah Dokumen: event = "unggah_dokumen" AND details != "unggah_dokumen"
     * - Inject - Mapping Dokumen: event = "mapping_dokumen" AND details LIKE "%inject%"
     *
     * MAPPING DOKUMEN (Non-inject only):
     * - event = "mapping_dokumen" AND details NOT LIKE "%inject%"
     *
     * UNGGAH DOKUMEN (Normal only):
     * - event = "unggah_dokumen" AND details = "unggah_dokumen"
     *
     * Lainnya: sesuai event_name asli
     */
    private function generateSummary(): void
    {
        $this->command->info("Generating summary table...");

        // Kosongkan summary table dulu
        DB::table('pegawai_aktivitas_summary')->truncate();

        // Query dengan CASE WHEN untuk kategorisasi
        // Menggunakan REPLACE dan CONCAT untuk convert snake_case ke Title Case
        $sql = "
            INSERT INTO pegawai_aktivitas_summary (nip, kategori_aktivitas, total_aktivitas, last_activity_at, created_at, updated_at)
            SELECT
                created_by_nip,
                CASE
                    WHEN event_name = 'unggah_dokumen' AND details != 'unggah_dokumen'
                        THEN 'Inject - Unggah Dokumen'
                    WHEN event_name = 'mapping_dokumen' AND details LIKE '%inject%'
                        THEN 'Inject - Mapping Dokumen'
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
            WHERE created_by_nip IS NOT NULL
            GROUP BY created_by_nip, kategori_aktivitas
        ";

        DB::statement($sql);

        $totalSummary = DB::table('pegawai_aktivitas_summary')->count();
        $this->command->info("  ✓ Generated {$totalSummary} summary records!");

        // Tampilkan sample summary
        $this->showSampleSummary();
    }

    /**
     * Tampilkan sample summary untuk verifikasi
     */
    private function showSampleSummary(): void
    {
        $this->command->info("\n=== Sample Summary (Top 5 Pegawai) ===");

        $samples = DB::table('pegawai_aktivitas_summary as pas')
            ->join('pegawai as p', 'pas.nip', '=', 'p.nip')
            ->select('p.nama', 'pas.nip', DB::raw('SUM(pas.total_aktivitas) as total'))
            ->groupBy('p.nama', 'pas.nip')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        foreach ($samples as $sample) {
            $this->command->info("  • {$sample->nama} (NIP: {$sample->nip}) → {$sample->total} aktivitas");

            // Tampilkan breakdown per kategori
            $breakdown = DB::table('pegawai_aktivitas_summary')
                ->where('nip', $sample->nip)
                ->orderByDesc('total_aktivitas')
                ->get();

            foreach ($breakdown as $item) {
                $this->command->info("    - {$item->kategori_aktivitas}: {$item->total_aktivitas}");
            }
        }
    }
}
