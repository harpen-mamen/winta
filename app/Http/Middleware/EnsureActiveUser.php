<?php

namespace App\Http\Middleware;

class EnsureActiveUser
{
    public function handle(\Illuminate\Http\Request $request, \Closure $next)
    {
        if ($request->user() && ! $request->user()->aktif) {
            auth()->logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            abort(403, 'Akun tidak aktif. Hubungi administrator.');
        }
        return $next($request);
    }
}
