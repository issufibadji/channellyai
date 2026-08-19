<?php

namespace App\Listeners\Audit;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use OwenIt\Auditing\Models\Audit;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        if (! $event->user) {
            return;
        }

        /** @var User $user */
        $user = $event->user;

        Audit::create([
            'user_type' => null,
            'user_id' => null,
            'event' => 'login_failed',
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
