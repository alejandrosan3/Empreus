<?php

namespace App\Providers;

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
        view()->share('appVersion', json_decode(file_get_contents(base_path('composer.json')), true)['extra']['app-version'] ?? 'unknown');
    }
}
