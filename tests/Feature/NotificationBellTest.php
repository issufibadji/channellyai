<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManager;
use App\Livewire\Dashboard;
use App\Livewire\NotificationBell;
use App\Models\User;
use App\Notifications\UserAccountCreated;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_creating_a_user_notifies_the_new_account(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->set('name', 'New User')
            ->set('email', 'new-user@example.com')
            ->set('password', 'password123')
            ->call('save');

        $newUser = User::where('email', 'new-user@example.com')->first();

        Notification::assertSentTo($newUser, UserAccountCreated::class);
    }

    public function test_unread_count_updates_when_marking_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new UserAccountCreated());

        $notificationId = $user->notifications()->first()->id;

        $this->assertSame(1, $user->unreadNotifications()->count());

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->call('markAsRead', $notificationId);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_mark_all_as_read_clears_the_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new UserAccountCreated());
        $user->notify(new UserAccountCreated());

        $this->assertSame(2, $user->unreadNotifications()->count());

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->call('markAllAsRead');

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_dashboard_shows_empty_state_without_notifications(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Nenhuma notificação registrada.');
    }

    public function test_dashboard_lists_recent_notifications(): void
    {
        $user = User::factory()->create();
        $user->notify(new UserAccountCreated());

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('Bem-vindo!');
    }
}
