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
            $table->unsignedBigInteger('idPreregistro');
            $table->unsignedBigInteger('idCatEstadoInicio');
            $table->dateTime('fechaEstado');
            $table->foreign('idPreregistro')->references('idPreregistro')->on('pre_registros')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('idCatEstadoInicio')->references('idCatEstadoInicio')->on('cat_estado_inicios')->onUpdate('cascade')->onDelete('cascade');

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
