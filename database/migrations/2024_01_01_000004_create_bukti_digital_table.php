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
        Schema::create('bukti_digital', function (Blueprint $table) {
            $table->id('id_evidence');
            $table->unsignedBigInteger('id_kasus');
            $table->string('jenis_bukti');
            $table->string('file_url');
            $table->timestamp('created_date')->useCurrent();
            $table->timestamps();

            $table->foreign('id_kasus')
                  ->references('id_kasus')
                  ->on('kasus')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bukti_digital');
    }
};
