<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->two_factor_secret || ! $user->two_factor_confirmed_at) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
