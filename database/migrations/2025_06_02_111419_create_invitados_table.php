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
        Schema::create('invitados', function (Blueprint $table) {
            $table->id('idInvitado');
            $table->integer('idUsr')->nullable(); 
            $table->unsignedBigInteger('idAudiencia');
            $table->string('correo');
            $table->string('correoAlterno')->nullable();
            $table->string('nombre');
            $table->string('coHost')->default('false'); 
            $table->unsignedBigInteger('idCatSexo');
            $table->unsignedBigInteger('idCatTipoParte');
            $table->string('direccion');
            $table->timestamps();
            $table->foreign('idAudiencia')->references('idAudiencia')->on('audiencias')->onDelete('cascade');
            $table->foreign('idCatSexo')->references('idCatSexo')->on('cat_sexos')->onDelete('cascade');
            $table->foreign('idCatTipoParte')->references('idCatTipoParte')->on('cat_tipo_partes')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitados');
    }
};
