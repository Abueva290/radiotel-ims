<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! $request->user() || ! $request->user()->hasRole(...$roles)) {
            abort(403, 'You do not have access to this module.');
        }

        return $next($request);
    }
}