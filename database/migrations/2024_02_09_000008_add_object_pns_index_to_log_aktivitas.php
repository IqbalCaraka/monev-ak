<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OPTIMIZATION: Index untuk query Mapping/Inject per Object PNS
     * Index ini akan SANGAT mempercepat COUNT DISTINCT object_pns_id
     */
    public function up(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            // Composite index untuk Mapping Dokumen queries
            // (event_name, created_by_nip, object_pns_id)
            $table->index(['event_name', 'created_by_nip', 'object_pns_id'], 'idx_event_nip_object');

            // Index untuk object_pns_id (untuk DISTINCT count)
            $table->index('object_pns_id', 'idx_object_pns');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropIndex('idx_event_nip_object');
            $table->dropIndex('idx_object_pns');
        });
    }
};
