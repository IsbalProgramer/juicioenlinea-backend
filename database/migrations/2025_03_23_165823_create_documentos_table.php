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
            $table->unsignedBigInteger('idPreregistro')->nullable(); // Relación con pre_registros
            $table->unsignedBigInteger('idCatTipoDocumento')->nullable(); // Relación con cat_tipo_documentos
            $table->string('nombre');
            $table->longText('documento');
            $table->timestamps();

            // Clave foránea con pre_registros
            $table->foreign('idPreregistro')->references('idPreregistro')->on('pre_registros')->onDelete('cascade');
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
