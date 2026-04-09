<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawai_aktivitas_summary', function (Blueprint $table) {
            $table->integer('total_per_object_pns')->default(0)->after('total_aktivitas');
        });

        // Backfill existing data
        DB::statement("
            UPDATE pegawai_aktivitas_summary pas
            JOIN (
                SELECT
                    created_by_nip as nip,
                    CASE
                        WHEN event_name = 'unggah_dokumen' AND is_inject = 1
                            THEN 'Inject - Unggah Dokumen'
                        WHEN event_name = 'mapping_dokumen' AND is_inject = 1
                            THEN 'Inject - Mapping Dokumen'
                        WHEN event_name = 'unggah_dokumen' AND (is_inject = 0 OR is_inject IS NULL)
                            THEN 'Unggah Dokumen'
                        WHEN event_name = 'mapping_dokumen' AND (is_inject = 0 OR is_inject IS NULL)
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
                        WHEN event_name = 'approve_upload_dok_myasn'
                            THEN 'Approval Dokumen MyASN'
                        ELSE CONCAT(UPPER(SUBSTRING(REPLACE(event_name, '_', ' '), 1, 1)),
                                   LOWER(SUBSTRING(REPLACE(event_name, '_', ' '), 2)))
                    END AS kategori_aktivitas,
                    COUNT(DISTINCT object_pns_id) as total_object_pns
                FROM log_aktivitas
                WHERE created_by_nip IS NOT NULL
                GROUP BY created_by_nip, kategori_aktivitas
            ) calc ON pas.nip = calc.nip AND pas.kategori_aktivitas = calc.kategori_aktivitas
            SET pas.total_per_object_pns = calc.total_object_pns
        ");
    }

    public function down(): void
    {
        Schema::table('pegawai_aktivitas_summary', function (Blueprint $table) {
            $table->dropColumn('total_per_object_pns');
        });
    }
};
