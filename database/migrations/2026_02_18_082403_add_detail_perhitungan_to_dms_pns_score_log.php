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
        Schema::table('dms_pns_score_log', function (Blueprint $table) {
            $table->json('detail_perhitungan')->nullable()->after('status_arsip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dms_pns_score_log', function (Blueprint $table) {
            $table->dropColumn('detail_perhitungan');
        });
    }
};
