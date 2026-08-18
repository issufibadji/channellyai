<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Database\Seeders\MenuSideBarSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(MenuSideBarSeeder::class);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_user_with_manager_role_can_view_the_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('manager');

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_admin_bypasses_permission_checks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_dashboard_link_only_shows_for_users_with_permission(): void
    {
        $withPermission = User::factory()->create();
        $withPermission->assignRole('manager');

        $withoutPermission = User::factory()->create();

        $this->actingAs($withPermission)
            ->get(route('dashboard'))
            ->assertSee('Usuários');

        $this->actingAs($withoutPermission)
            ->get(route('dashboard'))
            ->assertDontSee('Usuários');
    }
}
