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
        Schema::create('historial_estado_inicios', function (Blueprint $table) {
            $table->id('idHistorialEstadoInicio');
            $table->unsignedBigInteger('idInicio');
            $table->unsignedBigInteger('idCatEstadoInicio');
            $table->dateTime('fechaEstado');
            $table->foreign('idInicio')->references('idInicio')->on('inicios')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_inicios');
    }
};
