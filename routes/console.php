<?php
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ESTA ES LA ESTRUCTURA CORRECTA para Laravel 11 en routes/console.php
return function (Schedule $schedule) {
    $schedule->command('audiencias:guardar-grabaciones')->everyTwoMinutes();
};