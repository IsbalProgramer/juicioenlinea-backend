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
        Schema::create('inicios', function (Blueprint $table) {
            $table->id();
            $table->string('folio_preregistro')->unique();
            $table->string('materia',2);
            $table->string('via');
            $table->binary('archivo')->nullable(); // Almacena datos binarios
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inicios');
    }
};
