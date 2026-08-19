<?php

namespace App\Listeners\Audit;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use OwenIt\Auditing\Models\Audit;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        Audit::create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'event' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => [],
            'new_values' => [],
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tags' => 'auth',
        ]);
    }
}
