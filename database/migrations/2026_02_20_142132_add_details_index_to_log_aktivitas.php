<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For TEXT columns, we need to use raw SQL with key length
        // Indexing first 100 characters for prefix matching
        DB::statement('CREATE INDEX idx_details ON log_aktivitas(details(100))');
        DB::statement('CREATE INDEX idx_details ON log_aktivitas_staging(details(100))');
    }

    public function down(): void
    {
        Schema::table('log_aktivitas', function (Blueprint $table) {
            $table->dropIndex('idx_details');
        });

        Schema::table('log_aktivitas_staging', function (Blueprint $table) {
            $table->dropIndex('idx_details');
        });
    }
};
