<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Covering index for mapping dokumen query (non-inject)
        // Covers: WHERE event_name + is_inject, GROUP BY created_by_nip, COUNT DISTINCT object_pns_id
        DB::statement('CREATE INDEX idx_mapping_covering ON log_aktivitas(event_name, is_inject, created_by_nip, object_pns_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX idx_mapping_covering ON log_aktivitas');
    }
};
