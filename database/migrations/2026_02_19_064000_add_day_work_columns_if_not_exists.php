<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add columns to log_aktivitas if they don't exist
        if (!Schema::hasColumn('log_aktivitas', 'day_name')) {
            DB::statement('ALTER TABLE log_aktivitas ADD COLUMN day_name VARCHAR(20) NULL AFTER object_pns_id');
        }

        if (!Schema::hasColumn('log_aktivitas', 'work_category')) {
            DB::statement('ALTER TABLE log_aktivitas ADD COLUMN work_category VARCHAR(20) NULL AFTER day_name');
        }

        // Add columns to log_aktivitas_staging if they don't exist
        if (!Schema::hasColumn('log_aktivitas_staging', 'day_name')) {
            DB::statement('ALTER TABLE log_aktivitas_staging ADD COLUMN day_name VARCHAR(20) NULL AFTER object_pns_id');
        }

        if (!Schema::hasColumn('log_aktivitas_staging', 'work_category')) {
            DB::statement('ALTER TABLE log_aktivitas_staging ADD COLUMN work_category VARCHAR(20) NULL AFTER day_name');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('log_aktivitas', 'day_name')) {
            Schema::table('log_aktivitas', function ($table) {
                $table->dropColumn('day_name');
            });
        }

        if (Schema::hasColumn('log_aktivitas', 'work_category')) {
            Schema::table('log_aktivitas', function ($table) {
                $table->dropColumn('work_category');
            });
        }

        if (Schema::hasColumn('log_aktivitas_staging', 'day_name')) {
            Schema::table('log_aktivitas_staging', function ($table) {
                $table->dropColumn('day_name');
            });
        }

        if (Schema::hasColumn('log_aktivitas_staging', 'work_category')) {
            Schema::table('log_aktivitas_staging', function ($table) {
                $table->dropColumn('work_category');
            });
        }
    }
};
