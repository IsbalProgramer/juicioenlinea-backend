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
        Schema::create('grabaciones', function (Blueprint $table) {
            $table->id('idGrabacion');
            $table->unsignedBigInteger('idAudiencia');
            $table->string('id');
            $table->string('topic');
            $table->string('meetingSeriesId');
            $table->string('timeRecorded');
            $table->string('downloadUrl');
            $table->string('playbackUrl');
            $table->string('password');
            $table->string('durationSeconds');
            $table->timestamps();

            $table->foreign('idAudiencia')->references('idAudiencia')->on('audiencias')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grabaciones');
    }
};
