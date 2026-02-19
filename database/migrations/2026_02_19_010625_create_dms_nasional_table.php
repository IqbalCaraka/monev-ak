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
        Schema::create('dms_nasional', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('dms_uploads')->onDelete('cascade');
            $table->date('upload_date');

            // Statistics
            $table->integer('total_instansi')->default(0);
            $table->integer('total_pns')->default(0);

            // Average scores
            $table->decimal('avg_skor_nasional_system', 5, 2)->nullable();
            $table->decimal('avg_skor_nasional_csv', 5, 2)->nullable();
            $table->decimal('min_skor_instansi', 5, 2)->nullable();
            $table->decimal('max_skor_instansi', 5, 2)->nullable();

            // Distribution counts
            $table->integer('count_sangat_lengkap')->default(0);
            $table->integer('count_lengkap')->default(0);
            $table->integer('count_cukup_lengkap')->default(0);
            $table->integer('count_kurang_lengkap')->default(0);

            // Status
            $table->string('calculation_status')->default('pending'); // pending, processing, completed, failed
            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('upload_id');
            $table->index('calculation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dms_nasional');
    }
};
