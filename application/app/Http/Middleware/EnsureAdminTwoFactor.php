<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        // Admin tanpa 2FA tetap dapat mengakses area admin. Syarat 2FA untuk
        // mengaktifkan periode ditegakkan terpisah oleh PeriodReadinessService,
        // sehingga middleware ini tidak lagi memaksa redirect ke pengaturan keamanan.
        return $next($request);
    }
}
