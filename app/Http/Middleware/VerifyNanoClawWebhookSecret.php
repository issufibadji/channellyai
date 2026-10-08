<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNanoClawWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('nanoclaw.webhook_secret');

        abort_if(! $secret, 401, 'Webhook não configurado.');
        abort_unless(hash_equals($secret, (string) $request->header('X-NanoClaw-Secret')), 401);

        return $next($request);
    }
}
