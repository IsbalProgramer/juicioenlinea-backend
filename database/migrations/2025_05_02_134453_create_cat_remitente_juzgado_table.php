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
        Schema::create('cat_remitente_juzgados', function (Blueprint $table) {
            $table->id('idCatRemitenteJuzgado');
            $table->unsignedBigInteger('idCatJuzgado');
            $table->unsignedBigInteger('idCatRemitente');
            $table->timestamps();
            $table->foreign('idCatJuzgado')->references('idCatJuzgado')->on('cat_juzgados');
            $table->foreign('idCatRemitente')->references('idCatRemitente')->on('cat_remitentes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_remitente_juzgado');
    }
};
