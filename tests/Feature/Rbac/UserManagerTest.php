<?php

namespace Tests\Feature\Rbac;

use App\Livewire\Admin\UserManager;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->set('name', 'New User')
            ->set('email', 'new-user@example.com')
            ->set('password', 'password123')
            ->set('roles', ['manager'])
            ->call('save');

        $user = User::where('email', 'new-user@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('manager'));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_toggling_active_blocks_the_user_from_logging_in(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create(['active' => true]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('toggleActive', $target->id);

        $this->assertFalse($target->fresh()->active);
    }

    public function test_toggling_requires_2fa(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $target = User::factory()->create(['requires_2fa' => false]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('toggleRequires2fa', $target->id);

        $this->assertTrue($target->fresh()->requires_2fa);
    }
}
