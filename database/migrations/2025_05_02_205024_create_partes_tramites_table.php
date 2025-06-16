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
        Schema::create('partes_tramites', function (Blueprint $table) {
            // $table->id('idParte');
            // $table->unsignedBigInteger('idTramite');
            // $table->string('nombre');
            // // $table->string('apellidoMaterno');
            // // $table->string('apellidoPaterno');
            // $table->string('direccion');
            // $table->unsignedBigInteger('idCatSexo');
            // $table->unsignedBigInteger('idCatTipoParte');
            
            // $table->foreign('idTramite')->references('idTramite')->on ('tramites')->onDelete('cascade');
            // $table->foreign('idCatSexo')->references('idCatSexo')->on('cat_sexos')->onUpdate('cascade')->onDelete('cascade');
            // $table->foreign('idCatTipoParte')->references('idCatTipoParte')->on('cat_tipo_partes')->onUpdate('cascade')->onDelete('cascade');
            // $table->timestamps();
              $table->id('idParte');
            $table->unsignedBigInteger('idTramite');
            $table->unsignedBigInteger('idUsr')->nullable();
            $table->string('nombre');
            $table->string('correo');
            $table->string('direccion');
            $table->unsignedBigInteger('idCatSexo');
            $table->unsignedBigInteger('idCatTipoParte');
            $table->foreign('idTramite')->references('idTramite')->on ('tramites')->onDelete('cascade');
            $table->foreign('idCatSexo')->references('idCatSexo')->on('cat_sexos')->onUpdate('cascade');
            $table->foreign('idCatTipoParte')->references('idCatTipoParte')->on('cat_tipo_partes')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partes_tramites');
    }
};
