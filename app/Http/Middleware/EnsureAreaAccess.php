<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a page unless the person's role gives at least the needed level in an area.
 *
 *   ->middleware('area:lots')        at least View
 *   ->middleware('area:lots,edit')   at least Edit
 */
class EnsureAreaAccess
{
    public function handle(Request $request, Closure $next, string $area, string $level = 'view'): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasLevel($area, $level)) {
            // The dashboard is everyone's landing page (after sign-in, after "View as").
            // Roles without it go to the first page they can open instead.
            if ($area === 'dashboard' && ($home = self::firstPageFor($user))) {
                return redirect()->route($home);
            }

            abort(403, "You don't have access to this page. Ask an admin to change your role if you need it.");
        }

        return $next($request);
    }

    /** Landing pages in order of preference, with the area each needs. */
    private const HOME_PAGES = [
        'task-progress' => 'tasks',
        'task-scheduler' => 'scheduler',
        'lot-progress' => 'lots',
        'builders' => 'directory',
        'workers' => 'crews',
        'settings' => 'people',
    ];

    public static function firstPageFor($user): ?string
    {
        foreach (self::HOME_PAGES as $route => $area) {
            if ($user->hasLevel($area, 'view')) {
                return $route;
            }
        }

        return $user->hasLevel('settings', 'view') ? 'settings' : null;
    }
}
