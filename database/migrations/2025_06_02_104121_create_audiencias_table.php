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
        Schema::create('audiencias', function (Blueprint $table) {
            $table->id('idAudiencia');
            $table->string('folio')->unique();
            $table->unsignedBigInteger('idExpediente');
            $table->string('title');
            $table->string('agenda')->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('webLink');
            $table->string('hostEmail');
            $table->string('id');
            $table->string('meetingNumber');
            $table->string('password');
            $table->string('folio');
            //agregar estado y crear la siguiente tabla
            $table->timestamps();
            $table->foreign('idExpediente')->references('idExpediente')->on('expedientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audiencias');
    }
};
