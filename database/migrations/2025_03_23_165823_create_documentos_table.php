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
            $table->bigInteger('idCatTipoDocumento')->nullable(); // Relación con cat_tipo_documentos
            $table->string('nombre')->nullable();
            $table->longText('documento');
            $table->string('folio')->nullable(); //Folio para el acuerdo y el oficio Requerimiento -->Yo lo genero--> es continueo por cada expeidente 
            $table->string('idExpediente')->nullable(); // Relación con expedientes la uso para la continuedad del folio por expediente 
            //Es nullable para que no haya problemas al crear el documento
            $table->timestamps();

            // Clave foránea con pre_registros
            $table->foreign('idPreregistro')->references('idPreregistro')->on('pre_registros')->onDelete('cascade');
            $table->foreign('idCatTipoDocumento')->references('idCatTipoDocumento')->on('cat_tipo_documentos')->onUpdate('cascade')->onDelete('cascade');
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
