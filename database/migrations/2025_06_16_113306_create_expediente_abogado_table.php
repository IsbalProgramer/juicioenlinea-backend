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
        Schema::create('expediente_abogado', function (Blueprint $table) {
            $table->id("idExpedienteAbogado");
            $table->unsignedBigInteger('idExpediente');
            $table->unsignedBigInteger('idAbogado');
            $table->timestamps();
            $table->foreign('idExpediente')->references('idExpediente')->on('expedientes')->onDelete('cascade');
            $table->foreign('idAbogado')->references('idAbogado')->on('abogados')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expediente_abogados');
    }
};
