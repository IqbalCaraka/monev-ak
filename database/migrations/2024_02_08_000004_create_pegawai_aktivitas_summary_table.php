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
        Schema::create('pegawai_aktivitas_summary', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18);
            $table->string('kategori_aktivitas', 100); // Inject Data, unggah_dokumen, mapping_dokumen, dll
            $table->integer('total_aktivitas')->default(0);
            $table->string('last_activity_at', 50)->nullable(); // Dari created_at_log
            $table->timestamps();

            // Unique constraint: satu pegawai hanya punya 1 row per kategori
            $table->unique(['nip', 'kategori_aktivitas'], 'unique_nip_kategori');

            // Index untuk query cepat
            $table->index('nip', 'idx_nip');

            // Tidak pakai foreign key karena ada NIP di log yang belum terdaftar di pegawai
            // Summary tetap dibuat untuk semua NIP dari log_aktivitas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_aktivitas_summary');
    }
};
