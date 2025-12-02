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
            $table->text('allowed_domains')->nullable()->after('sensitivity');
            $table->integer('baseline_script_count')->default(0)->after('allowed_domains');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitored_sites', function (Blueprint $table) {
            $table->dropColumn(['allowed_domains', 'baseline_script_count']);
        });
    }
};
