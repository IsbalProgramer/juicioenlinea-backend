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
        Schema::create('pre_registros', function (Blueprint $table) {
            $table->id('idPreregistro');
            $table->string('folioPreregistro')->unique();
            $table->unsignedBigInteger('idCatMateriaVia'); // Relación con cat_materia_via
            $table->string('sintesis')->nullable();
            $table->string('observaciones')->nullable();
            $table->dateTime('fechaCreada');
            $table->integer('idGeneral');
            $table->integer('usr');
            $table->unsignedBigInteger('idCatJuzgado')->nullable();
            $table->string('idExpediente')->nullable();
            $table->dateTime('fechaResponse')->nullable();
            $table->unsignedBigInteger('idSecretario')->nullable();

            $table->timestamps();

            // Definir la clave foránea
            $table->foreign('idCatMateriaVia')->references('idCatMateriaVia')->on('cat_materia_via')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_registros');
    }
};
