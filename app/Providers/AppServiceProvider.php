<?php

namespace App\Providers;

use App\Models\PreRegistro;
use App\Observers\PreRegistroObserver;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        PreRegistro::observe(PreRegistroObserver::class);

    }
}
