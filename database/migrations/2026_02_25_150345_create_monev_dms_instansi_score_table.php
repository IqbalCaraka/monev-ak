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
        Schema::create('monev_dms_instansi_score', function (Blueprint $table) {
            $table->id();
            $table->string('id_instansi', 50);
            $table->string('nama_instansi', 255);
            $table->date('upload_date');
            $table->decimal('monev_skor_instansi', 5, 2);
            $table->enum('monev_status_kelengkapan', ['Sangat Lengkap', 'Lengkap', 'Cukup Lengkap', 'Kurang Lengkap']);
            $table->timestamps();

            // Indexes
            $table->index('id_instansi');
            $table->index('upload_date');
            $table->unique(['id_instansi', 'upload_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monev_dms_instansi_score');
    }
};
