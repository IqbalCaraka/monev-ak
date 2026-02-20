<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->boolean('is_inject')->default(false)->after('details');
            $table->index('is_inject', 'idx_is_inject');
        });

        Schema::table('log_aktivitas_staging', function (Blueprint $table) {
            $table->boolean('is_inject')->default(false)->after('details');
            $table->index('is_inject', 'idx_is_inject');
        });

        // Populate is_inject based on existing data
        DB::statement("UPDATE log_aktivitas SET is_inject = 1 WHERE details LIKE '%inject%' OR details LIKE '%Inject%'");
        DB::statement("UPDATE log_aktivitas_staging SET is_inject = 1 WHERE details LIKE '%inject%' OR details LIKE '%Inject%'");
    }

    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropIndex('idx_is_inject');
            $table->dropColumn('is_inject');
        });

        Schema::table('log_aktivitas_staging', function (Blueprint $table) {
            $table->dropIndex('idx_is_inject');
            $table->dropColumn('is_inject');
        });
    }
};
