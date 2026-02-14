<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            // Add day_name column (Senin, Selasa, Rabu, etc.)
            $table->string('day_name', 10)->nullable()->after('created_at');

            // Add work_category column (WFA, WFO, Libur)
            $table->string('work_category', 10)->nullable()->after('day_name');

            // Add indexes for better query performance
            $table->index('day_name', 'idx_day_name');
            $table->index('work_category', 'idx_work_category');
            $table->index(['day_name', 'work_category'], 'idx_day_work');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_day_name');
            $table->dropIndex('idx_work_category');
            $table->dropIndex('idx_day_work');

            // Drop columns
            $table->dropColumn(['day_name', 'work_category']);
        });
    }
};
