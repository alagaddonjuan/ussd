<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied. Admins only.'], 403);
            }

            abort(403, 'Access denied. Administrators only.');
        }

        return $next($request);
    }
}
