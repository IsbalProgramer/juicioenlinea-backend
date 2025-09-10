<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cat_secretario_juzgado', function (Blueprint $table) {
            // Elimina la clave foránea antes de cambiar el tipo
            $table->dropForeign(['idCatJuzgado']);
        });

        Schema::table('cat_secretario_juzgado', function (Blueprint $table) {
            // Cambia el tipo de columna a integer
            $table->integer('idCatJuzgado')->change();
        });

        Schema::table('cat_secretario_juzgado', function (Blueprint $table) {
            // Vuelve a crear la clave foránea con la tabla y columna correctas
            $table->foreign('idCatJuzgado')
                ->references('IdCatJuzgado')
                ->on('OPV_CatJuzgados');
        });
    }

    public function down(): void
    {
        Schema::table('cat_secretario_juzgado', function (Blueprint $table) {
            $table->dropForeign(['idCatJuzgado']);
        });

        Schema::table('cat_secretario_juzgado', function (Blueprint $table) {
            $table->unsignedBigInteger('idCatJuzgado')->change();
        });

        Schema::table('cat_secretario_juzgado', function (Blueprint $table) {
            $table->foreign('idCatJuzgado')
                ->references('idCatJuzgado')
                ->on('cat_juzgados');
        });
    }
};