<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_subscribe(): void
    {
        $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://example.com/push/abc',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_subscribe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('push.subscribe'), [
            'endpoint' => 'https://example.com/push/abc',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://example.com/push/abc',
        ]);
    }

    public function test_subscribing_twice_with_same_endpoint_updates_instead_of_duplicating(): void
    {
        $user = User::factory()->create();

        $payload = [
            'endpoint' => 'https://example.com/push/abc',
            'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
        ];

        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('push.subscribe'), $payload)->assertOk();

        $this->assertSame(1, $user->pushSubscriptions()->count());
    }

    public function test_user_can_unsubscribe(): void
    {
        $user = User::factory()->create();
        $user->updatePushSubscription('https://example.com/push/abc', 'public-key', 'auth-token');

        $this->actingAs($user)->deleteJson(route('push.unsubscribe'), [
            'endpoint' => 'https://example.com/push/abc',
        ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://example.com/push/abc']);
    }
}
