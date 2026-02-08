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
        // Fix created_at_log column type to VARCHAR if it's TIME
        DB::statement('ALTER TABLE log_aktivitas MODIFY created_at_log VARCHAR(50) NULL');
        DB::statement('ALTER TABLE log_aktivitas_staging MODIFY created_at_log VARCHAR(50) NULL');
        DB::statement('ALTER TABLE pegawai_aktivitas_summary MODIFY last_activity_at VARCHAR(50) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original (though we don't really want to do this)
        DB::statement('ALTER TABLE log_aktivitas MODIFY created_at_log VARCHAR(50) NULL');
        DB::statement('ALTER TABLE log_aktivitas_staging MODIFY created_at_log VARCHAR(50) NULL');
        DB::statement('ALTER TABLE pegawai_aktivitas_summary MODIFY last_activity_at VARCHAR(50) NULL');
    }
};
