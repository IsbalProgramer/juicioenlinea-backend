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
            $table->unsignedBigInteger('idExpediente');
            $table->string('folioTramite');
            $table->unsignedBigInteger('idDocumento')->nullable();
            $table->unsignedBigInteger('idDocumentoNuevo')->nullable();
            $table->unsignedBigInteger('idSecretario');
            $table->timestamps();

            // Claves foráneas
            // $table->foreign('idExpediente')->references('idExpediente')->on('expedientes')->onDelete('cascade');
            // $table->foreign('idDocumento')->references('idDocumento')->on('documentos')->onDelete('cascade');
            // $table->foreign('idDocumentoNuevo')->references('idDocumento')->on('documentos')->onDelete('cascade');
            $table->foreign('idDocumento')->references('idDocumento')->on('documentos')->onDelete('set null');
            $table->foreign('idDocumentoNuevo')->references('idDocumento')->on('documentos')->onDelete('no action');

            $table->foreign('idSecretario')->references('id')->on('users')->onDelete('no action');
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
