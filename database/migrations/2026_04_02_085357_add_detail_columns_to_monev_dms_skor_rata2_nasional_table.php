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
        Schema::table('monev_dms_skor_rata2_nasional', function (Blueprint $table) {
            $table->integer('kurang_lengkap')->default(0)->after('status_kelengkapan')->comment('Jumlah ASN dengan status kurang lengkap');
            $table->integer('cukup_lengkap')->default(0)->after('kurang_lengkap')->comment('Jumlah ASN dengan status cukup lengkap');
            $table->integer('lengkap')->default(0)->after('cukup_lengkap')->comment('Jumlah ASN dengan status lengkap');
            $table->integer('sangat_lengkap')->default(0)->after('lengkap')->comment('Jumlah ASN dengan status sangat lengkap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monev_dms_skor_rata2_nasional', function (Blueprint $table) {
            $table->dropColumn(['kurang_lengkap', 'cukup_lengkap', 'lengkap', 'sangat_lengkap']);
        });
    }
};
