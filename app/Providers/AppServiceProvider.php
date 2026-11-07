<?php

namespace App\Providers;

use App\Http\Middleware\SetCurrentCompany;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Support\Facades\Gate;
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

        $this->registerPermissions();
    }

    /**
     * Abilities read like the role editor:
     *   view-lots, edit-tasks, full-settings   (area levels)
     *   see-money, see-contacts, see-notes     (sensitive details)
     *   platform                               (platform owner only)
     *
     * Use them anywhere Laravel checks abilities: @can('edit-tasks') in Blade,
     * $this->authorize('edit-tasks') in Livewire, or ->can('view-lots') on routes.
     */
    private function registerPermissions(): void
    {
        $levels = implode('|', array_keys(config('permissions.levels')));

        Gate::before(function (User $user, string $ability) use ($levels) {
            if ($ability === 'platform') {
                return $user->isPlatformAdmin();
            }

            if (preg_match("/^($levels)-([a-z_]+)$/", $ability, $m) && array_key_exists($m[2], config('permissions.areas'))) {
                return $user->hasLevel($m[2], $m[1]);
            }

            if (preg_match('/^see-([a-z_]+)$/', $ability, $m) && array_key_exists($m[1], config('permissions.details'))) {
                return $user->sees($m[1]);
            }

            return null;
        });
    }
}
