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
        Schema::create('historial_estado_audiencias', function (Blueprint $table) {
            $table->id("idHistorialEstadoAudiencia");
            $table->unsignedBigInteger('idAudiencia');
            $table->unsignedBigInteger('idCatalogoEstadoAudiencia');
            $table->dateTime('fechaHora');
            $table->string('observaciones')->nullable();
            $table->unsignedBigInteger('idDocumento')->nullable();;

            $table->timestamps();

            $table->foreign('idAudiencia')->references('idAudiencia')->on('audiencias')->onDelete('cascade');
            $table->foreign('idCatalogoEstadoAudiencia')->references('idCatalogoEstadoAudiencia')->on('cat_estado_audiencias')->onDelete('cascade');
            $table->foreign('idDocumento')->references('idDocumento')->on('documentos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_audiencias');
    }
};
