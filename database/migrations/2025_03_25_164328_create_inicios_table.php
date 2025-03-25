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
        Schema::create('inicios', function (Blueprint $table) {
            $table->id('idInicio');
            $table->string('folio_preregistro')->unique();
            $table->unsignedBigInteger('idCatMateria');
            $table->integer('idCatVia');
            $table->dateTime('fechaCreada');
            //$table->binary('archivo');
            $table->integer('idAbogado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inicios');
    }
};
