<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsip_pensiun_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pengupload', 255);
            $table->string('zip_filename', 255);
            $table->string('zip_path', 500);
            $table->integer('jumlah_dokumen')->default(0);
            $table->string('status', 20)->default('pending'); // pending, extracting, completed, failed
            $table->text('message')->nullable();
            $table->timestamp('tanggal_unggah')->useCurrent();
            $table->timestamps();
        });

        Schema::create('arsip_pensiun_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('arsip_pensiun_uploads')->onDelete('cascade');
            $table->string('original_filename', 500);
            $table->string('stored_filename', 500);
            $table->string('file_path', 500);
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();

            $table->index('upload_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_pensiun_files');
        Schema::dropIfExists('arsip_pensiun_uploads');
    }
};
