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
     * Menambahkan composite index untuk optimasi filter tanggal
     * Index ini akan mempercepat query yang filter berdasarkan created_at_log
     */
    public function up(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            // Composite index untuk filter tanggal + NIP (untuk GROUP BY)
            $table->index(['created_at_log', 'created_by_nip'], 'idx_date_nip');

            // Index untuk event_name juga (untuk filter kategori + tanggal)
            $table->index(['created_at_log', 'event_name'], 'idx_date_event');
        });

        Schema::table('log_aktivitas_staging', function (Blueprint $table) {
            // Index yang sama untuk staging table
            $table->index(['created_at_log', 'created_by_nip'], 'idx_staging_date_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropIndex('idx_date_nip');
            $table->dropIndex('idx_date_event');
        });

        Schema::table('log_aktivitas_staging', function (Blueprint $table) {
            $table->dropIndex('idx_staging_date_nip');
        });
    }
};
