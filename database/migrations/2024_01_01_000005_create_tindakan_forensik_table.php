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
        Schema::create('tindakan_forensik', function (Blueprint $table) {
            $table->id('id_tindakan');
            $table->unsignedBigInteger('id_kasus');
            $table->string('jenis_tindakan');
            $table->dateTime('waktu_pelaksanaan');
            $table->string('lokasi_tindakan');
            $table->text('metode_forensik');
            $table->text('hasil_tindakan')->nullable();
            $table->string('petugas_forensik');
            $table->enum('status_tindakan', ['Planned', 'In Progress', 'Completed'])->default('Planned');
            $table->text('catatan')->nullable();
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
        Schema::dropIfExists('tindakan_forensik');
    }
};
