<?php

namespace Tests\Feature\Rbac;

use App\Livewire\Admin\PermissionManager;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_manage_permissions_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.permissions.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_permission_with_tag(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(PermissionManager::class)
            ->set('name', 'export-reports')
            ->set('tag', 'reports')
            ->call('save');

        $this->assertDatabaseHas('permissions', ['name' => 'export-reports', 'tag' => 'reports']);
    }
}
