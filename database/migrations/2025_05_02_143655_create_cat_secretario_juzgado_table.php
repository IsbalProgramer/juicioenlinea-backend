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
        Schema::create('cat_secretario_juzgado', function (Blueprint $table) {
            $table->id('idCatSecretarioJuzgado');
            $table->unsignedBigInteger('idUsr');
            $table->unsignedBigInteger('idGeneral');
            $table->unsignedBigInteger('idCatJuzgado');
            $table->timestamps();

            $table->foreign('idCatJuzgado')->references('idCatJuzgado')->on('cat_juzgados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_secretario_juzgado');
    }
};
