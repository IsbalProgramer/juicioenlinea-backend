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
        Schema::create('remitentes', function (Blueprint $table) {
            $table->id('idRemitente');
            $table->unsignedBigInteger('idTramite');
            $table->integer('categoria');
            $table->integer('depedencia');
            $table->integer('remitente');
            $table->integer('cargo');

            $table->foreign('idTramite')->references('idTramite')->on('tramites')->onUpdate('cascade')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remitentes');
    }
};
