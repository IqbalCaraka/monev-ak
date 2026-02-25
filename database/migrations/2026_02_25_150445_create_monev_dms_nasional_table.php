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
        Schema::create('monev_dms_nasional', function (Blueprint $table) {
            $table->id();
            $table->date('upload_date')->unique();
            $table->integer('total_instansi');
            $table->decimal('monev_skor_nasional', 5, 2);
            $table->integer('count_sangat_lengkap')->default(0);
            $table->integer('count_lengkap')->default(0);
            $table->integer('count_cukup_lengkap')->default(0);
            $table->integer('count_kurang_lengkap')->default(0);
            $table->timestamps();

            // Index
            $table->index('upload_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monev_dms_nasional');
    }
};
