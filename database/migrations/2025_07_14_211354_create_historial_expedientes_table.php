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
        Schema::create('historial_expedientes', function (Blueprint $table) {
            $table->id('idHistorialExpediente');
            $table->foreignId('idExpediente');
            $table->unsignedBigInteger('idEstadoExpediente')->default(1); // Nuevo campo
            $table->text('descripcion');
            $table->timestamps();

            $table->foreign('idEstadoExpediente')->references('idEstadoExpediente')->on('cat_estado_expediente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_expedientes');
    }
};
