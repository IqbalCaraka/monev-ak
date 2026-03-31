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
        Schema::table('csv_processing_jobs', function (Blueprint $table) {
            // Rename kolom agar sesuai dengan Job class
            if (Schema::hasColumn('csv_processing_jobs', 'job_id')) {
                $table->dropColumn('job_id');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'filename')) {
                $table->renameColumn('filename', 'csv_filename');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'file_path')) {
                $table->renameColumn('file_path', 'csv_path');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'error_message')) {
                $table->renameColumn('error_message', 'message');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'result_file')) {
                $table->renameColumn('result_file', 'output_file');
            }

            // Drop kolom progress karena tidak dipakai
            if (Schema::hasColumn('csv_processing_jobs', 'progress')) {
                $table->dropColumn('progress');
            }

            // Tambah kolom user_id jika belum ada
            if (!Schema::hasColumn('csv_processing_jobs', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }

            // Update status column menjadi string biasa (bukan enum)
            $table->string('status', 50)->default('pending')->change();

            // Update message column menjadi text nullable
            $table->text('message')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csv_processing_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('csv_processing_jobs', 'job_id')) {
                $table->string('job_id')->unique()->after('id');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'csv_filename')) {
                $table->renameColumn('csv_filename', 'filename');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'csv_path')) {
                $table->renameColumn('csv_path', 'file_path');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'message')) {
                $table->renameColumn('message', 'error_message');
            }

            if (Schema::hasColumn('csv_processing_jobs', 'output_file')) {
                $table->renameColumn('output_file', 'result_file');
            }

            if (!Schema::hasColumn('csv_processing_jobs', 'progress')) {
                $table->integer('progress')->default(0);
            }

            if (Schema::hasColumn('csv_processing_jobs', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
