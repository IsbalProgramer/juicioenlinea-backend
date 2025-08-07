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
            $table->unsignedBigInteger('idCatEstadoRequerimientos')->default(1);  //Al crear uno inicia en 1 -- CREADO por default
            $table->unsignedBigInteger('idUsuario');
            $table->timestamps();

            // Claves foráneas
                        
            // ...existing code...
            $table->foreign('idRequerimiento')
                ->references('idRequerimiento')
                ->on('requerimientos')
                ->name('fk_historial_req');
            
            $table->foreign('idCatEstadoRequerimientos')
                ->references('idCatEstadoRequerimientos')
                ->on('cat_estado_requerimientos')
                ->name('fk_historial_estado_req');
                   // $table->foreign('idUsuario')->references('id')->on('users');
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
