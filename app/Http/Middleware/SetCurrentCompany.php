<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the division (stored in the `companies` table) the person is working in.
 *
 * The choice is kept in the session as `company_id`. If it is missing, or points to
 * a division the person can't access, the first division they can access is used.
 */
class SetCurrentCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            tenancy()->forget();

            return $next($request);
        }

        $division = null;
        if ($id = $request->session()->get('company_id')) {
            $division = Company::with('business.organization')->find($id);
        }

        if (! $division || ! $user->canAccessDivision($division)) {
            $division = $user->accessibleDivisions()->with('business.organization')->first();
        }

        if ($division) {
            $request->session()->put('company_id', $division->id);
        } else {
            $request->session()->forget('company_id');
        }

        tenancy()->setDivision($division);
        $request->attributes->set('currentCompany', $division);

        return $next($request);
    }
}
