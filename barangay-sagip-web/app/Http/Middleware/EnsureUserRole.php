<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Register in bootstrap/app.php:
 *   $middleware->alias(['role' => \App\Http\Middleware\EnsureUserRole::class]);
 *
 * Usage on routes:
 *   Route::middleware('role:official')->group(...)
 *   Route::middleware('role:official,personnel')->group(...)
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
