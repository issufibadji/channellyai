<?php

namespace Tests\Feature\Rbac;

use App\Livewire\Admin\UserManager;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
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
        $this->seed(AcademicoPermissionSeeder::class);
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

    public function test_manager_can_create_a_user_with_an_allowed_role(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->set('name', 'Novo Professor')
            ->set('email', 'novo-professor@example.com')
            ->set('password', 'password123')
            ->set('roles', ['professor'])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'novo-professor@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('professor'));
    }

    public function test_manager_cannot_create_a_user_with_admin_role(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->set('name', 'Tentativa Admin')
            ->set('email', 'tentativa-admin@example.com')
            ->set('password', 'password123')
            ->set('roles', ['admin'])
            ->call('save')
            ->assertHasErrors('roles.0');

        $this->assertNull(User::where('email', 'tentativa-admin@example.com')->first());
    }

    public function test_manager_cannot_promote_an_existing_professor_to_admin(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $professor = User::factory()->create();
        $professor->assignRole('professor');

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->call('edit', $professor->id)
            ->set('roles', ['admin'])
            ->call('save')
            ->assertHasErrors('roles.0');

        $this->assertFalse($professor->fresh()->hasRole('admin'));
    }

    public function test_manager_can_edit_and_delete_a_professor(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $professor = User::factory()->create();
        $professor->assignRole('professor');

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->call('edit', $professor->id)
            ->set('name', 'Professor Renomeado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Professor Renomeado', $professor->fresh()->name);

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->call('delete', $professor->id);

        $this->assertDatabaseMissing('users', ['id' => $professor->id]);
    }

    public function test_manager_gets_403_editing_an_admin(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->call('edit', $admin->id)
            ->assertForbidden();
    }

    public function test_manager_gets_403_deleting_another_manager(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $otherManager = User::factory()->create();
        $otherManager->assignRole('manager');

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->call('delete', $otherManager->id)
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherManager->id]);
    }

    public function test_manager_does_not_see_admins_or_other_managers_in_the_user_list(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $admin = User::factory()->create(['name' => 'Zé Admin']);
        $admin->assignRole('admin');

        $otherManager = User::factory()->create(['name' => 'Zé Manager']);
        $otherManager->assignRole('manager');

        $professor = User::factory()->create(['name' => 'Zé Professor']);
        $professor->assignRole('professor');

        Livewire::actingAs($manager)
            ->test(UserManager::class)
            ->assertDontSee('Zé Admin')
            ->assertDontSee('Zé Manager')
            ->assertSee('Zé Professor');
    }
}
