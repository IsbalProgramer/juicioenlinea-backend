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
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id('idExpediente');
            $table->string('NumExpediente');
            $table->unsignedBigInteger('idCatJuzgado');
            $table->dateTime('fechaResponse');
            $table->unsignedBigInteger('idPreregistro');
            $table->foreign('idPreregistro')->references('idPreregistro')->on('pre_registros')->onUpdate('cascade')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
