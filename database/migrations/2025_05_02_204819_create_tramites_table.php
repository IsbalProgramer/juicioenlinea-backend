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
            // $table->string('usr');
            $table->string('idUsr')->nullable();
            $table->string('folioOficio');
            $table->string('sintesis');
            $table->string('observaciones');
            $table->unsignedBigInteger('idExpediente');
            $table->boolean('notificado')->default(false);
            $table->integer('idDocumentoTramite');
            $table->unsignedBigInteger('idCatRemitente')->nullable();

            $table->foreign('idCatTramite')->references('idCatTramite')->on('cat_tramites');
            $table->foreign('idCatRemitente')->references('idCatRemitente')->on('cat_remitentes');

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
