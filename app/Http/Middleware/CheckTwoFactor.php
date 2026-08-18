<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->two_factor_confirmed_at && ! session('2fa_passed')) {
            return redirect()->route('two-factor.challenge');
        }

        if ($user && $user->requires_2fa && ! $user->two_factor_confirmed_at) {
            return redirect()->route('settings.two-factor')
                ->with('warning', 'Sua conta exige verificação em duas etapas. Configure para continuar.');
        }

        return $next($request);
    }
}
