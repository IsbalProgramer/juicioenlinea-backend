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
        Schema::create('cat_materia_via', function (Blueprint $table) {
            $table->id('idCatMateriaVia');
            $table->unsignedBigInteger('idCatTipoVia'); // Relación con cat_vias
            $table->unsignedBigInteger('idCatMateria'); // Relación con cat_materias
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Definir las claves foráneas
            $table->foreign('idCatMateria')->references('idCatMateria')->on('cat_materias')->onDelete('cascade');
            $table->foreign('idCatTipoVia')->references('idCatTipoVia')->on('cat_tipo_vias')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_materia_via');
    }
};
