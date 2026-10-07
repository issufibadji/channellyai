<?php

namespace App\Http\Middleware;

use App\Services\CurrentEstabelecimento;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentEstabelecimento
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $current = app(CurrentEstabelecimento::class);

        if (! $user) {
            return $next($request);
        }

        if ($user->hasRole('admin')) {
            return $next($request);
        }

        $vinculos = $user->estabelecimentos()->pluck('estabelecimentos.id');

        if ($current->id() && ! $vinculos->contains($current->id())) {
            $current->clear();
        }

        if (! $current->id() && $vinculos->count() === 1) {
            $current->set($vinculos->first());
        }

        if (! $current->id() && $vinculos->isEmpty()) {
            return redirect()->route('atendimento.sem-estabelecimento');
        }

        return $next($request);
    }
}
