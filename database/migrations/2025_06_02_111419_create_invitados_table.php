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
        Schema::create('invitados', function (Blueprint $table) {
            $table->id('idInvitado');
            $table->unsignedBigInteger('idAudiencia');
            $table->string('email');
            $table->string('displayName');
            $table->string('coHost')->default('false'); // 'true' or 'false' as string
            $table->timestamps();

            $table->foreign('idAudiencia')->references('idAudiencia')->on('audiencias')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitados');
    }
};
