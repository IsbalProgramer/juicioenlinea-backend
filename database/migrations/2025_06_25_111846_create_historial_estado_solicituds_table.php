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
        Schema::create('historial_estado_solicituds', function (Blueprint $table) {
            $table->id('idHistorialEstadoSolicitud'); 
            $table->unsignedBigInteger('idSolicitud');
            $table->unsignedBigInteger('idCatalogoEstadoSolicitud');
            $table->dateTime('fechaEstado');  
            $table->unsignedBigInteger('idDocumento')->nullable();
            $table->timestamps();
            
            //relacion con iddocumento
            $table->foreign('idDocumento')->references('idDocumento')->on('documentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_solicituds');
    }
};
