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
        Schema::create('monev_dms_skor_rata2_nasional', function (Blueprint $table) {
            $table->id();
            $table->date('upload_date')->unique()->comment('Tanggal upload data');
            $table->integer('jumlah_asn')->default(0)->comment('Total jumlah ASN nasional');
            $table->decimal('skor_rata2_nasional', 5, 2)->comment('Rata-rata skor DMS nasional');
            $table->enum('status_kelengkapan', ['Sangat Lengkap', 'Lengkap', 'Cukup Lengkap', 'Kurang Lengkap'])
                ->comment('Status kelengkapan berdasarkan skor');
            $table->timestamps();

            $table->index('upload_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monev_dms_skor_rata2_nasional');
    }
};
