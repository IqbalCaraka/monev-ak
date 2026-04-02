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
        Schema::rename('monev_dms_nasional', 'monev_dms_skor_rata2_instansi');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('monev_dms_skor_rata2_instansi', 'monev_dms_nasional');
    }
};
