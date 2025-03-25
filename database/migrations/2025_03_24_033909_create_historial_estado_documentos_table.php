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
            $table->unsignedBigInteger('idDocumento')->nullable();
            $table->dateTime('fechaEstado');
            $table->unsignedBigInteger('idCatEstadoDocumento');
            $table->unsignedBigInteger('idUsuario')->nullable();

            $table->timestamps();

            // Claves foráneas
            $table->foreign('idCatEstadoDocumento')->references('idCatalogoEstadoDocumento')->on('cat_estado_documento');
            $table->foreign('idUsuario')->references('id')->on('users')->onDelete('SET NULL');
            $table->foreign('idDocumento')->references('idDocumento')->on('documentos')->onDelete('set null');
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
