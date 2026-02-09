<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PIC (Person In Charge) System:
     * - PIC memiliki ketua (dari pegawai)
     * - PIC punya anggota tim (many pegawai)
     * - PIC pegang beberapa instansi (many instansi)
     */
    public function up(): void
    {
        // Tabel utama PIC
        Schema::create('pic', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100); // Nama PIC/Tim
            $table->text('deskripsi')->nullable(); // Deskripsi tugas PIC
            $table->string('ketua_nip', 18); // NIP Ketua PIC
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index
            $table->index('ketua_nip');
            $table->index('is_active');
        });

        // Pivot table: PIC - Pegawai (Anggota Tim)
        Schema::create('pic_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pic_id')->constrained('pic')->onDelete('cascade');
            $table->string('pegawai_nip', 18);
            $table->string('role', 50)->default('anggota'); // 'ketua' atau 'anggota'
            $table->date('assigned_at'); // Tanggal ditugaskan
            $table->timestamps();

            // Unique constraint: 1 pegawai tidak boleh duplicate di 1 PIC
            $table->unique(['pic_id', 'pegawai_nip']);

            // Indexes
            $table->index('pegawai_nip');
            $table->index(['pic_id', 'role']);

            // Foreign key ke pegawai
            $table->foreign('pegawai_nip')->references('nip')->on('pegawai')->onDelete('cascade');
        });

        // Pivot table: PIC - Instansi (Wilayah Kerja)
        Schema::create('pic_instansi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pic_id')->constrained('pic')->onDelete('cascade');
            $table->string('instansi_id'); // STRING karena instansi.id adalah STRING
            $table->date('assigned_at'); // Tanggal ditugaskan
            $table->timestamps();

            // Unique constraint: 1 instansi tidak boleh duplicate di 1 PIC
            $table->unique(['pic_id', 'instansi_id']);

            // Indexes
            $table->index('instansi_id');

            // Foreign key manual (karena tipe STRING)
            $table->foreign('instansi_id')->references('id')->on('instansi')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pic_instansi');
        Schema::dropIfExists('pic_pegawai');
        Schema::dropIfExists('pic');
    }
};
