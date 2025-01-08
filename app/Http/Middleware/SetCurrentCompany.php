<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = null;

        if ($request->session()->has('company_id')) {
            $companyId = $request->session()->get('company_id');
        } else {
            if ($request->user()) {
                $company = $request->user()->companies()->first(); // Get the first company the user belongs to
                if ($company) {
                    $companyId = $company->id;
                    $request->session()->put('company_id', $companyId);
                }
            }
        }

        if ($companyId && $company = Company::find($companyId)) {
            // Check if the logged-in user belongs to the selected company
            if ($company->users->contains($request->user()->id)) {
                $request->attributes->set('currentCompany', $company);
            } else {
                // Handle unauthorized access (e.g., clear session, redirect)
                $request->session()->forget('company_id');
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
