<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename kategori dari nama ELSE clause ke nama yang benar
        DB::table('pegawai_aktivitas_summary')
            ->where('kategori_aktivitas', 'Approve upload dok myasn')
            ->update(['kategori_aktivitas' => 'Approval Dokumen MyASN']);

        // Backfill total_per_object_pns untuk approval rows
        DB::statement("
            UPDATE pegawai_aktivitas_summary pas
            JOIN (
                SELECT created_by_nip as nip, COUNT(DISTINCT object_pns_id) as total_object_pns
                FROM log_aktivitas
                WHERE event_name = 'approve_upload_dok_myasn'
                AND created_by_nip IS NOT NULL
                GROUP BY created_by_nip
            ) calc ON pas.nip = calc.nip
            SET pas.total_per_object_pns = calc.total_object_pns
            WHERE pas.kategori_aktivitas = 'Approval Dokumen MyASN'
        ");
    }

    public function down(): void
    {
        DB::table('pegawai_aktivitas_summary')
            ->where('kategori_aktivitas', 'Approval Dokumen MyASN')
            ->update(['kategori_aktivitas' => 'Approve upload dok myasn']);
    }
};
