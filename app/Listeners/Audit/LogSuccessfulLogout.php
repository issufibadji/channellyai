<?php

namespace App\Listeners\Audit;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use OwenIt\Auditing\Models\Audit;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        /** @var User $user */
        $user = $event->user;

        Audit::create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'event' => 'logout',
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
