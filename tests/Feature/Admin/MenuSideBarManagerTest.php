<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MenuSideBarManager;
use App\Models\MenuSideBar;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuSideBarManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_manage_menu_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.menu.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_menu_item(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(MenuSideBarManager::class)
            ->set('label', 'Relatórios')
            ->set('icon', 'chart-bar')
            ->set('routeName', 'dashboard')
            ->set('order', 5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('menu_side_bars', ['label' => 'Relatórios', 'icon' => 'chart-bar']);
    }

    public function test_creating_a_menu_item_with_a_nonexistent_route_fails(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(MenuSideBarManager::class)
            ->set('label', 'Relatórios')
            ->set('routeName', 'rota.que.nao.existe')
            ->call('save')
            ->assertHasErrors('routeName');

        $this->assertDatabaseMissing('menu_side_bars', ['label' => 'Relatórios']);
    }

    public function test_creating_a_menu_item_with_an_invalid_icon_fails(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(MenuSideBarManager::class)
            ->set('label', 'Chatbot')
            ->set('icon', 'bot')
            ->set('routeName', 'dashboard')
            ->call('save')
            ->assertHasErrors('icon');

        $this->assertDatabaseMissing('menu_side_bars', ['label' => 'Chatbot']);
    }

    public function test_admin_can_update_a_menu_item(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $item = MenuSideBar::create(['label' => 'Antigo', 'route_name' => 'dashboard', 'order' => 1]);

        Livewire::actingAs($admin)
            ->test(MenuSideBarManager::class)
            ->call('edit', $item->id)
            ->set('label', 'Novo Nome')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Novo Nome', $item->fresh()->label);
    }

    public function test_admin_can_delete_a_menu_item(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $item = MenuSideBar::create(['label' => 'Removível', 'route_name' => 'dashboard', 'order' => 1]);

        Livewire::actingAs($admin)
            ->test(MenuSideBarManager::class)
            ->call('delete', $item->id);

        $this->assertDatabaseMissing('menu_side_bars', ['id' => $item->id]);
    }

    public function test_saving_a_menu_item_notifies_the_sidebar(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(MenuSideBarManager::class)
            ->set('label', 'Relatórios')
            ->set('routeName', 'dashboard')
            ->call('save')
            ->assertDispatched('menu-updated');
    }
}
