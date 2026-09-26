<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccountStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Disabled while logged in → kick out immediately
        if (! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been disabled. Please contact the administrator.']);
        }

        // First login or reset by admin → must set own password first
        if ($user->must_change_password
            && ! $request->routeIs('password.change', 'password.change.store', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}