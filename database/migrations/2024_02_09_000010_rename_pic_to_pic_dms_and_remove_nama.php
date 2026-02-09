<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if pic table exists (not yet renamed)
        if (Schema::hasTable('pic') && !Schema::hasTable('pic_dms')) {
            // Rename pivot tables first
            if (Schema::hasTable('pic_pegawai')) {
                Schema::rename('pic_pegawai', 'pic_dms_pegawai');
            }
            if (Schema::hasTable('pic_instansi')) {
                Schema::rename('pic_instansi', 'pic_dms_instansi');
            }

            // Rename main table
            Schema::rename('pic', 'pic_dms');
        }

        // Drop old foreign keys if they exist (using raw SQL with IF EXISTS check)
        if (Schema::hasColumn('pic_dms_pegawai', 'pic_id')) {
            // Check and drop foreign key manually
            $fkExists = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'pic_dms_pegawai'
                AND CONSTRAINT_NAME = 'pic_dms_pegawai_pic_id_foreign'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            if ($fkExists[0]->count > 0) {
                DB::statement('ALTER TABLE pic_dms_pegawai DROP FOREIGN KEY pic_dms_pegawai_pic_id_foreign');
            }

            // Also try pic_pegawai_pic_id_foreign (old name before table rename)
            $fkExists2 = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'pic_dms_pegawai'
                AND CONSTRAINT_NAME = 'pic_pegawai_pic_id_foreign'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            if ($fkExists2[0]->count > 0) {
                DB::statement('ALTER TABLE pic_dms_pegawai DROP FOREIGN KEY pic_pegawai_pic_id_foreign');
            }

            // Rename pic_id to pic_dms_id
            Schema::table('pic_dms_pegawai', function (Blueprint $table) {
                $table->renameColumn('pic_id', 'pic_dms_id');
            });
        }

        if (Schema::hasColumn('pic_dms_instansi', 'pic_id')) {
            // Check and drop foreign key manually
            $fkExists = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'pic_dms_instansi'
                AND CONSTRAINT_NAME = 'pic_dms_instansi_pic_id_foreign'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            if ($fkExists[0]->count > 0) {
                DB::statement('ALTER TABLE pic_dms_instansi DROP FOREIGN KEY pic_dms_instansi_pic_id_foreign');
            }

            // Also try pic_instansi_pic_id_foreign (old name before table rename)
            $fkExists2 = DB::select("
                SELECT COUNT(*) as count
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'pic_dms_instansi'
                AND CONSTRAINT_NAME = 'pic_instansi_pic_id_foreign'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            if ($fkExists2[0]->count > 0) {
                DB::statement('ALTER TABLE pic_dms_instansi DROP FOREIGN KEY pic_instansi_pic_id_foreign');
            }

            // Rename pic_id to pic_dms_id
            Schema::table('pic_dms_instansi', function (Blueprint $table) {
                $table->renameColumn('pic_id', 'pic_dms_id');
            });
        }

        // Add foreign keys if not exist
        Schema::table('pic_dms_pegawai', function (Blueprint $table) {
            if (!$this->foreignKeyExists('pic_dms_pegawai', 'pic_dms_pegawai_pic_dms_id_foreign')) {
                $table->foreign('pic_dms_id')->references('id')->on('pic_dms')->onDelete('cascade');
            }
        });

        Schema::table('pic_dms_instansi', function (Blueprint $table) {
            if (!$this->foreignKeyExists('pic_dms_instansi', 'pic_dms_instansi_pic_dms_id_foreign')) {
                $table->foreign('pic_dms_id')->references('id')->on('pic_dms')->onDelete('cascade');
            }
        });

        // Remove nama and deskripsi from pic_dms if they exist
        if (Schema::hasColumn('pic_dms', 'nama')) {
            Schema::table('pic_dms', function (Blueprint $table) {
                $table->dropColumn(['nama', 'deskripsi']);
            });
        }
    }

    /**
     * Check if foreign key exists
     */
    private function foreignKeyExists($table, $foreignKey)
    {
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND CONSTRAINT_NAME = ?
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ", [$table, $foreignKey]);

        return $result[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back nama and deskripsi
        Schema::table('pic_dms', function (Blueprint $table) {
            $table->string('nama', 100)->after('id');
            $table->text('deskripsi')->nullable()->after('nama');
        });

        // Drop foreign keys
        Schema::table('pic_dms_pegawai', function (Blueprint $table) {
            $table->dropForeign(['pic_dms_id']);
        });

        Schema::table('pic_dms_instansi', function (Blueprint $table) {
            $table->dropForeign(['pic_dms_id']);
        });

        // Rename back pic_dms_id to pic_id
        Schema::table('pic_dms_pegawai', function (Blueprint $table) {
            $table->renameColumn('pic_dms_id', 'pic_id');
        });

        Schema::table('pic_dms_instansi', function (Blueprint $table) {
            $table->renameColumn('pic_dms_id', 'pic_id');
        });

        // Add foreign keys back
        Schema::table('pic_dms_pegawai', function (Blueprint $table) {
            $table->foreign('pic_id')->references('id')->on('pic_dms')->onDelete('cascade');
        });

        Schema::table('pic_dms_instansi', function (Blueprint $table) {
            $table->foreign('pic_id')->references('id')->on('pic_dms')->onDelete('cascade');
        });

        // Rename tables back
        Schema::rename('pic_dms', 'pic');
        Schema::rename('pic_dms_pegawai', 'pic_pegawai');
        Schema::rename('pic_dms_instansi', 'pic_instansi');
    }
};
