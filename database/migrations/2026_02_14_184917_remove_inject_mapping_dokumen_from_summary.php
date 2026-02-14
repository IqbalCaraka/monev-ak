<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove all "Inject - Mapping Dokumen" entries from summary table
        DB::table('pegawai_aktivitas_summary')
            ->where('kategori_aktivitas', 'Inject - Mapping Dokumen')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - data was intentionally removed
        // If needed to restore, regenerate summary from log_aktivitas
    }
};
