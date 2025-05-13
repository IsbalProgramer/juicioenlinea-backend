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
        Schema::create('requerimientos', function (Blueprint $table) {
            $table->id('idRequerimiento');
            $table->string('descripcion');
            $table->string('idExpediente');
            $table->unsignedBigInteger('idDocumentoAcuerdo');
            $table->unsignedBigInteger('idDocumentoAuto')->nullable();
            $table->unsignedBigInteger('idDocumentoAcuse')->nullable();
            $table->unsignedBigInteger('idSecretario');
            $table->unsignedBigInteger('usuarioSecretario');
            $table->unsignedBigInteger('idAbogado');
            $table->unsignedBigInteger('usuarioAbogado')->nullable();
            $table->timestamps();
            // $table->dateTime('fechaLimite');
            $table->dateTime('fechaLimite', 6); // precisión de 6 dígitos = microsegundos
            
            // Claves foráneas
            // $table->foreign('idExpediente')->references('idExpediente')->on('expedientes');
            $table->foreign('idDocumentoAcuerdo')->references('idDocumento')->on('documentos');
            // $table->foreign('idSecretario')->references('id')->on('users')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requerimientos');
    }
};
