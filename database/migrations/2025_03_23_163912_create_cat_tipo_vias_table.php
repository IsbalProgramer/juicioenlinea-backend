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
        Schema::create('cat_tipo_vias', function (Blueprint $table) {
            $table->id('idCatTipoVia');
            $table->string('descripcion');
            $table->string('modelo');
            $table->boolean('activo')->default(true);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_vias');
    }
};
