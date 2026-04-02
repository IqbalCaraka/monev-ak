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
            // Tambah kolom untuk data manual dari database server
            $table->integer('jumlah_asn')->default(0)->after('monev_skor_instansi')->comment('Total jumlah ASN di instansi');
            $table->integer('sangat_lengkap')->default(0)->after('jumlah_asn')->comment('Jumlah ASN dengan kelengkapan Sangat Lengkap (80-100)');
            $table->integer('lengkap')->default(0)->after('sangat_lengkap')->comment('Jumlah ASN dengan kelengkapan Lengkap (60-79)');
            $table->integer('cukup_lengkap')->default(0)->after('lengkap')->comment('Jumlah ASN dengan kelengkapan Cukup Lengkap (40-59)');
            $table->integer('kurang_lengkap')->default(0)->after('cukup_lengkap')->comment('Jumlah ASN dengan kelengkapan Kurang Lengkap (0-39)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monev_dms_instansi_score', function (Blueprint $table) {
            $table->dropColumn(['jumlah_asn', 'sangat_lengkap', 'lengkap', 'cukup_lengkap', 'kurang_lengkap']);
        });
    }
};
