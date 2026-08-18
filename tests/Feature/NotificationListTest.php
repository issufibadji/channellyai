<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationListTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_page_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $user->notify(new UserAccountCreated());

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Bem-vindo!');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }
}
