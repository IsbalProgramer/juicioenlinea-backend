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
        Schema::create('documento_requerimiento', function (Blueprint $table) {
            $table->id('idDocumentosRequerimiento');
            $table->unsignedBigInteger('idRequerimiento');
            $table->unsignedBigInteger('idDocumento');
            $table->timestamps();
        
            // Claves foráneas
            $table->foreign('idRequerimiento')->references('idRequerimiento')->on('requerimientos')->onDelete('no action');
            $table->foreign('idDocumento')->references('idDocumento')->on('documentos')->onDelete('no action');
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_requerimiento_pivot');
    }
};
