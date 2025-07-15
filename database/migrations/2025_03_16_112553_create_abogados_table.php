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
        Schema::create('abogados', function (Blueprint $table) {
            $table->id('idAbogado');
            // $table->integer('idUsr');
             $table->string('idUsr')->nullable();
            $table->integer('idGeneral')->nullable(); 
            $table->string('nombre');
            $table->string('correo');
            $table->string('correoAlterno')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abogados');
    }
};
