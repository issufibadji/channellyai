<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AnnouncementManager;
use App\Models\AppConfig;
use App\Models\User;
use App\Notifications\SystemAnnouncementNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.announcements.index'))->assertForbidden();
    }

    public function test_sending_to_all_notifies_every_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $others = User::factory()->count(2)->create();

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->set('title', 'Manutenção programada')
            ->set('message', 'O sistema ficará indisponível às 22h.')
            ->set('target', 'all')
            ->set('channels', ['database'])
            ->call('send');

        Notification::assertSentTo([$admin, ...$others], SystemAnnouncementNotification::class);
        $this->assertDatabaseHas('system_announcements', ['title' => 'Manutenção programada']);
    }

    public function test_sending_to_a_role_only_notifies_users_with_that_role(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $operator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->set('title', 'Aviso pros managers')
            ->set('message', 'Reunião às 10h.')
            ->set('target', 'role:manager')
            ->set('channels', ['database'])
            ->call('send');

        Notification::assertSentTo($manager, SystemAnnouncementNotification::class);
        Notification::assertNotSentTo($operator, SystemAnnouncementNotification::class);
    }

    public function test_webhook_channel_posts_to_the_configured_url(): void
    {
        Http::fake();

        AppConfig::create(['key' => 'webhook_url', 'value' => 'https://webhook.example.com/notify']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->set('title', 'Aviso geral')
            ->set('message', 'Mensagem de teste.')
            ->set('target', 'all')
            ->set('channels', ['webhook'])
            ->call('send');

        Http::assertSent(fn ($request) => $request->url() === 'https://webhook.example.com/notify'
            && $request['title'] === 'Aviso geral');
    }

    public function test_deleting_an_announcement_removes_only_the_history_record(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->set('title', 'Aviso')
            ->set('message', 'Mensagem.')
            ->set('target', 'all')
            ->set('channels', ['database'])
            ->call('send');

        $announcement = \App\Models\SystemAnnouncement::first();

        Livewire::actingAs($admin)
            ->test(AnnouncementManager::class)
            ->call('delete', $announcement->id);

        $this->assertDatabaseMissing('system_announcements', ['id' => $announcement->id]);
    }
}
