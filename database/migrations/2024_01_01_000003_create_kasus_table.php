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
        Schema::create('kasus', function (Blueprint $table) {
            $table->id('id_kasus');
            $table->unsignedBigInteger('id_korban');
            $table->string('jenis_kasus');
            $table->date('tanggal_kejadian');
            $table->text('deskripsi_kasus');
            $table->enum('status_kasus', ['Open', 'Closed'])->default('Open');
            $table->timestamps();

            $table->foreign('id_korban')
                  ->references('id_korban')
                  ->on('korban')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kasus');
    }
};
