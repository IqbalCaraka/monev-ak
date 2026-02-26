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
        Schema::table('monev_dms_instansi_score', function (Blueprint $table) {
            $table->string('kantor_regional_id', 10)->after('monev_skor_instansi')->nullable();
            $table->index('kantor_regional_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monev_dms_instansi_score', function (Blueprint $table) {
            $table->dropIndex(['kantor_regional_id']);
            $table->dropColumn('kantor_regional_id');
        });
    }
};
