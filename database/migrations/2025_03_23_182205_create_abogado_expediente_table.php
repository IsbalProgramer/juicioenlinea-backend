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
        Schema::create('abogado_expediente', function (Blueprint $table) {
            $table->id('idAbogadoExpediente');
            $table->unsignedBigInteger('idAbogado');
            $table->string('idExpediente');
            $table->unsignedBigInteger('Activo')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abogado_expediente');
    }
};
