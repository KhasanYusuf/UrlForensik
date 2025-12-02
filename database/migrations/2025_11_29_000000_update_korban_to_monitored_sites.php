<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration renames `korban` -> `monitored_sites`, renames the PK
     * `id_korban` -> `id_site`, drops unused columns and adds new ones.
     * It also updates the `kasus` table to reference `id_site` and adds
     * the requested columns.
     *
     * NOTE: This migration uses raw ALTER statements for column renames to
     * avoid requiring the doctrine/dbal package. It's written for MySQL.
     */
    public function up(): void
    {
        // 1) Rename korban table to monitored_sites
        if (Schema::hasTable('korban')) {
            Schema::rename('korban', 'monitored_sites');
        }

        // 2) Rename primary key column id_korban -> id_site and adjust type
        if (Schema::hasTable('monitored_sites') && Schema::hasColumn('monitored_sites', 'id_korban')) {
            // MySQL: CHANGE current_name new_name definition
            DB::statement("ALTER TABLE `monitored_sites` CHANGE `id_korban` `id_site` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }

        // 3) Drop unwanted columns and add new ones
        Schema::table('monitored_sites', function (Blueprint $table) {
            if (Schema::hasColumn('monitored_sites', 'kontak_korban')) {
                $table->dropColumn('kontak_korban');
            }
            if (Schema::hasColumn('monitored_sites', 'lokasi_korban')) {
                $table->dropColumn('lokasi_korban');
            }

            if (! Schema::hasColumn('monitored_sites', 'site_url')) {
                $table->string('site_url')->after('nama_korban');
            }
            if (! Schema::hasColumn('monitored_sites', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('site_url');
            }
            if (! Schema::hasColumn('monitored_sites', 'baseline_hash')) {
                $table->string('baseline_hash')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('monitored_sites', 'status')) {
                $table->enum('status', ['UP', 'DOWN', 'DEFACED'])->default('UP')->after('baseline_hash');
            }
        });

        // 4) Update kasus table: drop FK to korban, rename column to id_site, add new columns and FK
        Schema::table('kasus', function (Blueprint $table) {
            // drop foreign key if it exists
            try {
                $table->dropForeign(['id_korban']);
            } catch (\Exception $e) {
                // ignore if it doesn't exist
            }

            // rename column id_korban -> id_site (use raw SQL if necessary)
            if (Schema::hasColumn('kasus', 'id_korban')) {
                // MySQL change column (assumes unsigned bigint referencing PK)
                DB::statement("ALTER TABLE `kasus` CHANGE `id_korban` `id_site` BIGINT UNSIGNED NULL");
            }

            if (! Schema::hasColumn('kasus', 'detection_source')) {
                $table->enum('detection_source', ['Manual', 'System Monitoring'])->default('System Monitoring')->after('deskripsi_kasus');
            }
            if (! Schema::hasColumn('kasus', 'impact_level')) {
                $table->enum('impact_level', ['Low', 'Medium', 'High'])->default('Low')->after('detection_source');
            }

            // add foreign key to monitored_sites
            try {
                $table->foreign('id_site')->references('id_site')->on('monitored_sites')->onDelete('cascade');
            } catch (\Exception $e) {
                // ignore if FK cannot be created (existing data or naming issues)
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1) Update kasus: drop FK to monitored_sites, rename id_site back to id_korban, drop added columns
        Schema::table('kasus', function (Blueprint $table) {
            try {
                $table->dropForeign(['id_site']);
            } catch (\Exception $e) {
            }

            if (Schema::hasColumn('kasus', 'id_site')) {
                DB::statement("ALTER TABLE `kasus` CHANGE `id_site` `id_korban` BIGINT UNSIGNED NULL");
            }

            if (Schema::hasColumn('kasus', 'impact_level')) {
                $table->dropColumn('impact_level');
            }
            if (Schema::hasColumn('kasus', 'detection_source')) {
                $table->dropColumn('detection_source');
            }
        });

        // 2) monitored_sites: drop added columns, rename id_site back to id_korban and rename table back
        Schema::table('monitored_sites', function (Blueprint $table) {
            if (Schema::hasColumn('monitored_sites', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('monitored_sites', 'baseline_hash')) {
                $table->dropColumn('baseline_hash');
            }
            if (Schema::hasColumn('monitored_sites', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('monitored_sites', 'site_url')) {
                $table->dropColumn('site_url');
            }
        });

        if (Schema::hasTable('monitored_sites') && Schema::hasColumn('monitored_sites', 'id_site')) {
            DB::statement("ALTER TABLE `monitored_sites` CHANGE `id_site` `id_korban` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }

        if (Schema::hasTable('monitored_sites')) {
            Schema::rename('monitored_sites', 'korban');
        }

        // 3) restore kontak_korban and lokasi_korban if table exists
        Schema::table('korban', function (Blueprint $table) {
            if (! Schema::hasColumn('korban', 'kontak_korban')) {
                $table->string('kontak_korban')->nullable()->after('nama_korban');
            }
            if (! Schema::hasColumn('korban', 'lokasi_korban')) {
                $table->string('lokasi_korban')->nullable()->after('kontak_korban');
            }
        });
    }
};
