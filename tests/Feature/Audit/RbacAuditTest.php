<?php

namespace Tests\Feature\Audit;

use App\Livewire\Admin\PermissionManager;
use App\Livewire\Admin\RoleManager;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RbacAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The package disables auditing for console-run processes by default;
        // tests run via `artisan test`, so we opt back in for these assertions.
        config(['audit.console' => true]);

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_creating_a_role_is_audited(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(RoleManager::class)
            ->set('name', 'supervisor')
            ->call('save');

        $this->assertDatabaseHas('audits', [
            'event' => 'created',
            'auditable_type' => Role::class,
            'user_id' => $admin->id,
        ]);
    }

    public function test_creating_a_permission_is_audited(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(PermissionManager::class)
            ->set('name', 'export-relatorios')
            ->call('save');

        $this->assertDatabaseHas('audits', [
            'event' => 'created',
            'auditable_type' => Permission::class,
            'user_id' => $admin->id,
        ]);
    }
}
