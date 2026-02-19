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
        // DROP OLD TABLES (if exists)
        Schema::dropIfExists('dms_instansi_scores');
        Schema::dropIfExists('dms_pns_scores');
        Schema::dropIfExists('dms_pns_score_log');
        Schema::dropIfExists('dms_pns');
        Schema::dropIfExists('dms_uploads');

        // 1. CREATE dms_uploads (tracking upload CSV)
        Schema::create('dms_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 255);
            $table->dateTime('upload_date');
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('upload_date');
            $table->index('status');
        });

        // 2. CREATE dms_pns (MASTER PNS - tidak duplikat)
        Schema::create('dms_pns', function (Blueprint $table) {
            $table->id();
            $table->string('pns_id', 50); // UUID from CSV
            $table->string('nip', 18)->unique();
            $table->string('nama', 255);
            $table->char('status_cpns_pns', 1); // P or C
            $table->string('instansi_id', 50);
            $table->string('instansi_nama', 255);
            $table->timestamps();

            $table->index('pns_id');
            $table->index('nip');
            $table->index('instansi_id');
        });

        // 3. CREATE dms_pns_score_log (HISTORY SCORING per upload)
        Schema::create('dms_pns_score_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('dms_uploads')->onDelete('cascade');
            $table->string('pns_id', 50); // UUID dari CSV (bukan foreign key ke dms_pns.id)
            $table->json('status_arsip'); // JSON dari CSV
            $table->decimal('skor_csv', 5, 2)->nullable(); // dari skor_arsip_2026 di CSV
            $table->decimal('skor_calculated', 5, 2)->nullable(); // dihitung dari status_arsip
            $table->string('status_kelengkapan', 50)->nullable(); // Kategori berdasarkan skor_csv
            $table->timestamps();

            $table->index('upload_id');
            $table->index('pns_id');
            $table->index(['upload_id', 'pns_id']);
            $table->index('status_kelengkapan');
        });

        // 4. CREATE dms_instansi_scores (AGREGASI per instansi per upload)
        Schema::create('dms_instansi_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('dms_uploads')->onDelete('cascade');
            $table->string('instansi_id', 50);
            $table->string('instansi_nama', 255);
            $table->dateTime('upload_date');

            // Statistics
            $table->integer('total_pns');
            $table->decimal('skor_instansi_calculated_system', 5, 2)->nullable(); // AVG dari skor_calculated
            $table->decimal('skor_instansi_calculated_csv', 5, 2)->nullable(); // AVG dari skor_csv
            $table->decimal('min_skor_calculated', 5, 2)->nullable();
            $table->decimal('max_skor_calculated', 5, 2)->nullable();

            // Score distribution (berdasarkan skor_calculated)
            $table->integer('count_80_100')->default(0); // Sangat Baik
            $table->integer('count_60_79')->default(0);  // Baik
            $table->integer('count_40_59')->default(0);  // Cukup
            $table->integer('count_0_39')->default(0);   // Kurang

            // Status kelengkapan (berdasarkan skor_csv rata-rata)
            $table->string('status_kelengkapan', 50)->nullable();

            // Calculation status
            $table->enum('calculation_status', ['pending', 'calculating', 'completed'])->default('pending');
            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            // Indexes (TIDAK ADA UNIQUE - karena ini log/history)
            $table->index('upload_id');
            $table->index('instansi_id');
            $table->index('calculation_status');
            $table->index(['instansi_id', 'upload_date']);
            $table->index(['upload_id', 'instansi_id', 'calculated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dms_instansi_scores');
        Schema::dropIfExists('dms_pns_score_log');
        Schema::dropIfExists('dms_pns');
        Schema::dropIfExists('dms_uploads');
    }
};
