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
        Schema::table('log_aktivitas', function (Blueprint $table) {
            // Add inject_type: 'unggah' or 'mapping' or null
            $table->string('inject_type', 20)->nullable()->after('is_inject');
            $table->index('inject_type', 'idx_inject_type');
        });

        Schema::table('log_aktivitas_staging', function (Blueprint $table) {
            $table->string('inject_type', 20)->nullable()->after('is_inject');
            $table->index('inject_type', 'idx_inject_type');
        });

        // Populate inject_type for existing data
        // Inject Unggah: event_name = 'unggah_dokumen' AND is_inject = 1
        DB::statement("
            UPDATE log_aktivitas
            SET inject_type = 'unggah'
            WHERE event_name = 'unggah_dokumen' AND is_inject = 1
        ");

        // Inject Mapping: event_name = 'mapping_dokumen' AND is_inject = 1
        DB::statement("
            UPDATE log_aktivitas
            SET inject_type = 'mapping'
            WHERE event_name = 'mapping_dokumen' AND is_inject = 1
        ");

        // Repeat for staging table
        DB::statement("
            UPDATE log_aktivitas_staging
            SET inject_type = 'unggah'
            WHERE event_name = 'unggah_dokumen' AND is_inject = 1
        ");

        DB::statement("
            UPDATE log_aktivitas_staging
            SET inject_type = 'mapping'
            WHERE event_name = 'mapping_dokumen' AND is_inject = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropIndex('idx_inject_type');
            $table->dropColumn('inject_type');
        });

        Schema::table('log_aktivitas_staging', function (Blueprint $table) {
            $table->dropIndex('idx_inject_type');
            $table->dropColumn('inject_type');
        });
    }
};
