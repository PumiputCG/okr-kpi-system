<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AppUserAuthController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! AppUserAuthController::isAdminRole($user->role)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Forbidden. Admin role required.',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
