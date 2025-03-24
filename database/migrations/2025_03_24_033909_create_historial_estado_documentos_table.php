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
        Schema::create('historial_estado_documento', function (Blueprint $table) {
            $table->id('idHistorialEstadoDocumento');
            $table->unsignedBigInteger('idDocumento');
            $table->dateTime('fechaEstado');
            $table->unsignedBigInteger('idCatEstadoDocumento');
            $table->unsignedBigInteger('idGeneral')->nullable();
            $table->timestamps();

            // Claves foráneas
            $table->foreign('idCatEstadoDocumento')->references('idCatalogoEstadoDocumento')->on('cat_estado_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_documentos');
    }
};
