<?php

namespace App\Providers;

use App\Models\PreRegistro;
use App\Observers\PreRegistroObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Schedule $schedule): void
    {
        $schedule->command('audiencias:guardar-grabaciones')->everyMinute();
    }
}
