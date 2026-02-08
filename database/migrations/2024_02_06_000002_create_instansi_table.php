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
        Schema::create('instansi', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('lokasi_id')->nullable();
            $table->string('nama');
            $table->string('jenis', 10)->nullable();
            $table->string('nama_baru')->nullable();
            $table->string('nama_jabatan')->nullable();
            $table->string('jenis_instansi_id', 20)->nullable();
            $table->string('kantor_regional_id', 10)->nullable();
            $table->string('prov_id', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instansi');
    }
};
