<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RESTORE "Inject - Mapping Dokumen" to summary table
     * Uses is_inject column for accurate detection
     */
    public function up(): void
    {
        // TRUNCATE summary table and re-populate with ALL categories
        // This ensures data consistency with the new is_inject column

        DB::statement('TRUNCATE TABLE pegawai_aktivitas_summary');

        $sql = "
            INSERT INTO pegawai_aktivitas_summary (nip, kategori_aktivitas, total_aktivitas, last_activity_at, created_at, updated_at)
            SELECT
                created_by_nip,
                CASE
                    -- Inject - Unggah Dokumen: unggah_dokumen dengan is_inject = 1
                    WHEN event_name = 'unggah_dokumen' AND is_inject = 1
                        THEN 'Inject - Unggah Dokumen'

                    -- Inject - Mapping Dokumen: mapping_dokumen dengan is_inject = 1
                    WHEN event_name = 'mapping_dokumen' AND is_inject = 1
                        THEN 'Inject - Mapping Dokumen'

                    -- Unggah Dokumen (normal): unggah_dokumen dengan is_inject = 0
                    WHEN event_name = 'unggah_dokumen' AND (is_inject = 0 OR is_inject IS NULL)
                        THEN 'Unggah Dokumen'

                    -- Mapping Dokumen (non-inject): mapping_dokumen dengan is_inject = 0
                    WHEN event_name = 'mapping_dokumen' AND (is_inject = 0 OR is_inject IS NULL)
                        THEN 'Mapping Dokumen'

                    -- Other categories
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

                    -- Default: capitalize event_name
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
            ON DUPLICATE KEY UPDATE
                total_aktivitas = VALUES(total_aktivitas),
                last_activity_at = VALUES(last_activity_at),
                updated_at = NOW()
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove "Inject - Mapping Dokumen" entries again
        DB::table('pegawai_aktivitas_summary')
            ->where('kategori_aktivitas', 'Inject - Mapping Dokumen')
            ->delete();
    }
};
