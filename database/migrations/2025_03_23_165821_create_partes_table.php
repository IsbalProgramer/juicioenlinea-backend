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
        Schema::create('partes', function (Blueprint $table) {
            $table->id('idParte');
            $table->unsignedBigInteger('idPreregistro');
            $table->string('nombre');
            $table->string('apellidoMaterno');
            $table->string('apellidoPaterno');
            $table->string('direccion');
            $table->unsignedBigInteger('idCatGenero');
            $table->unsignedBigInteger('idCatParte');
            $table->foreign('idPreregistro')->references('idPreregistro')->on('pre_registros')->onDelete('cascade');
            $table->foreign('idCatGenero')->references('idCatGenero')->on('cat_generos')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('idCatParte')->references('idCatParte')->on('cat_tipo_partes')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partes');
    }
};
