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
            // Kolom untuk mendefinisikan area statis (misal: nav, footer, sidebar)
            // Area ini akan dicek struktur HTML-nya secara ketat.
            $table->string('selector_static')
                  ->nullable()
                  ->after('site_url') // Menempatkan kolom setelah site_url (opsional)
                  ->comment('CSS Selectors untuk area layout tetap (misal: nav, footer, .sidebar). Area ini dilarang berubah strukturnya.');

            // Kolom untuk mendefinisikan area dinamis (misal: #content, .article-body)
            // Area ini boleh berubah teksnya, tapi akan discan untuk tag berbahaya (script/iframe).
            $table->string('selector_dynamic')
                  ->nullable()
                  ->after('selector_static')
                  ->comment('CSS Selectors untuk area konten dinamis (misal: #main, .post-content). Area ini boleh update teks, tapi discan untuk injeksi.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitored_sites', function (Blueprint $table) {
            $table->dropColumn(['selector_static', 'selector_dynamic']);
        });
    }
};
