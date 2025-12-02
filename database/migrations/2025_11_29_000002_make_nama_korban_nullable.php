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
        if (Schema::hasTable('monitored_sites') && Schema::hasColumn('monitored_sites', 'nama_korban')) {
            // Make existing nama_korban column nullable to allow creates where legacy
            // field is not provided (we now use `site_url`). This is safe and
            // reversible.
            DB::statement("ALTER TABLE `monitored_sites` MODIFY `nama_korban` VARCHAR(255) NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monitored_sites') && Schema::hasColumn('monitored_sites', 'nama_korban')) {
            DB::statement("ALTER TABLE `monitored_sites` MODIFY `nama_korban` VARCHAR(255) NOT NULL");
        }
    }
};
