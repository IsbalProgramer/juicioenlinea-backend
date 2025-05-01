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
        Schema::table('documentos', function (Blueprint $table) {
            // // Eliminar la columna 'folio'
            // $table->dropUnique(['folio']); // quitar el índice único primero
            // $table->dropColumn('folio');

            // Agregar columna 'idInicio'
            $table->unsignedBigInteger('idInicio')->nullable();

            // Agregar la clave foránea
            $table->foreign('idInicio')->references('idInicio')->on('inicios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
