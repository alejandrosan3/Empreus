<?php

namespace App\Providers;

use App\Http\Middleware\SetCurrentCompany;
use App\Support\Tenancy;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One per request (or per queued job), never shared between requests.
        $this->app->scoped(Tenancy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        view()->share('appVersion', json_decode(file_get_contents(base_path('composer.json')), true)['extra']['app-version'] ?? 'unknown');

        // Livewire button clicks and form saves arrive as separate requests; make sure
        // they run inside the selected division too.
        Livewire::addPersistentMiddleware([SetCurrentCompany::class]);
    }
}
