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
        Schema::create('tramites', function (Blueprint $table) {
            $table->id('idTramite');
            $table->unsignedBigInteger('idCatTramite');
            $table->integer('idGeneral');
            $table->string('tramiteOrigen');
            $table->string('folioOficio');
            $table->string('folioPreregistro');
            $table->string('sintesis');
            $table->string('observaciones')->nullable();
            $table->dateTime('fechaRecepcion')->nullable();
            $table->unsignedBigInteger('idExpediente');

            $table->foreign('idCatTramite')->references('idCatTramite')->on('cat_tramites')->onUpdate('cascade')->onDelete('cascade');
            // $table->foreign('idExpediente')->references('idExpediente')->on('expedientes')->onUpdate('cascade')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tramites');
    }
};
