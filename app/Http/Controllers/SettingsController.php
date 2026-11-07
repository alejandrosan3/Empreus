<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class SettingsController extends Controller
{
    /** /settings opens the first tab the person can see. */
    public function index(): RedirectResponse
    {
        foreach (config('settings.tabs') as $tab) {
            if (Route::has($tab['route']) && (! $tab['ability'] || Gate::allows($tab['ability']))) {
                return redirect()->route($tab['route']);
            }
        }

        return redirect()->route('profile');
    }

    /** Leave "View as this role". */
    public function stopViewingAs(): RedirectResponse
    {
        session()->forget('view_as_role_id');

        return redirect()->route('settings.roles')->with('status', 'Back to your own access.');
    }
}
