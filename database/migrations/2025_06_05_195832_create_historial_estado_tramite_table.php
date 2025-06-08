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
        Schema::create('historial_estado_tramite', function (Blueprint $table) {
           $table->id('idHistorialEstadoInicio');
            $table->unsignedBigInteger('idPreregistro');
            $table->unsignedBigInteger('idCatEstadoTramite');
            $table->unsignedBigInteger('idUsuario');
            $table->foreign('idPreregistro')->references('idPreregistro')->on('pre_registros');
            $table->foreign('idCatEstadoTramite')->references('idCatEstadoTramite')->on('cat_estado_tramite');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_tramite');
    }
};
