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
            Schema::create('documentos', function (Blueprint $table) {
                $table->id('idDocumento');
                $table->unsignedBigInteger('idExpediente');
                $table->string('folio');
                $table->string('nombre');
                $table->binary('documento');
                $table->timestamps();
    
                // Clave foránea
                //$table->foreign('idExpediente')->references('idExpediente')->on('expedientes')->onDelete('cascade');
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
