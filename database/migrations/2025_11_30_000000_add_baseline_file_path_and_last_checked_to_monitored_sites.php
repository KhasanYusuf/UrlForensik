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
        Schema::table('monitored_sites', function (Blueprint $table) {
            if (! Schema::hasColumn('monitored_sites', 'baseline_file_path')) {
                $table->string('baseline_file_path')->nullable()->after('baseline_hash');
            }
            if (! Schema::hasColumn('monitored_sites', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('baseline_file_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitored_sites', function (Blueprint $table) {
            if (Schema::hasColumn('monitored_sites', 'last_checked_at')) {
                $table->dropColumn('last_checked_at');
            }
            if (Schema::hasColumn('monitored_sites', 'baseline_file_path')) {
                $table->dropColumn('baseline_file_path');
            }
        });
    }
};
