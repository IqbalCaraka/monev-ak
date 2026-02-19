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
        Schema::create('dms_pns', function (Blueprint $table) {
            $table->id();
            $table->string('pns_id', 50)->unique(); // UUID from CSV
            $table->string('nip', 18)->unique();
            $table->string('nama', 255);
            $table->char('status_cpns_pns', 1); // P or C
            $table->string('instansi_id', 50);
            $table->string('instansi_nama', 255);
            $table->timestamps();

            // Indexes
            $table->index('nip');
            $table->index('pns_id');
            $table->index('instansi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dms_pns');
    }
};
