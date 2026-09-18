<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AppUserAuthController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenanceEnabled = (string) config('app.pb_maintenance', '0') === '1';

        if (! $isMaintenanceEnabled) {
            return $next($request);
        }

        $user = $request->user();

        // Keep admin users accessible during portal maintenance.
        if ($user && AppUserAuthController::isAdminRole($user->role)) {
            return $next($request);
        }

        // Let guests access public/login pages.
        if (! $user) {
            return $next($request);
        }

        // Keep login and logout pages available so users can return to welcome.
        if ($request->routeIs('welcome', 'landing', 'login', 'login.store', 'welcome.login', 'logout')) {
            return $next($request);
        }

        $lang = strtolower((string) $request->query('lang', 'th')) === 'en' ? 'en' : 'th';

        return response()->view('maintenance', [
            'lang' => $lang,
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}