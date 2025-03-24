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
        Schema::create('historial_estado_requerimientos', function (Blueprint $table) {
            $table->id('idHistorialEstadoRequerimientos');
            $table->unsignedBigInteger('idRequerimiento');
            $table->unsignedBigInteger('idEstadoRequerimiento');
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('idRequerimiento')->references('idRequerimiento')->on('requerimientos')->onDelete('cascade');
            $table->foreign('idEstadoRequerimiento')->references('idCatEstadoRequerimientos')->on('cat_estado_requerimientos')->onDelete('cascade');
            $table->foreign('idUsuario')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estado_requerimientos');
    }
};
