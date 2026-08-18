<?php

namespace Tests\Feature\Rbac;

use App\Livewire\Admin\RoleManager;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_manage_roles_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_role_with_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(RoleManager::class)
            ->set('name', 'supervisor')
            ->set('permissions', ['view-users'])
            ->call('save');

        $role = Role::where('name', 'supervisor')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('view-users'));
    }

    public function test_admin_can_delete_a_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::create(['name' => 'temporary']);

        Livewire::actingAs($admin)
            ->test(RoleManager::class)
            ->call('delete', $role->id);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
