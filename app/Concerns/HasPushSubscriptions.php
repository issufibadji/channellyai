<?php

namespace App\Concerns;

use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasPushSubscriptions
{
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function updatePushSubscription(string $endpoint, string $publicKey, string $authToken, ?string $contentEncoding = 'aesgcm'): PushSubscription
    {
        return $this->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $endpoint],
            ['public_key' => $publicKey, 'auth_token' => $authToken, 'content_encoding' => $contentEncoding],
        );
    }

    public function deletePushSubscription(string $endpoint): void
    {
        $this->pushSubscriptions()->where('endpoint', $endpoint)->delete();
    }
}
