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
        Schema::create('log_aktivitas_staging', function (Blueprint $table) {
            $table->string('id', 36)->primary(); // UUID
            $table->string('transaction_id', 36)->nullable();
            $table->string('event_name', 100)->index();
            $table->text('details')->nullable();
            $table->string('created_by_id', 36)->nullable();
            $table->string('created_by_nama')->nullable();
            $table->string('created_by_nip', 18)->nullable();
            $table->string('created_at_log', 50)->nullable(); // Format waktu dari CSV
            $table->string('object_pns_id', 36)->nullable();
            $table->timestamps(); // Laravel timestamps

            // Index untuk performa
            $table->index('created_by_nip', 'idx_staging_nip');
            $table->index(['created_by_nip', 'event_name'], 'idx_staging_composite');

            // Tidak ada foreign key karena ini staging area untuk NIP yang belum terdata
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas_staging');
    }
};
