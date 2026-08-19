<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\CanalManager;
use App\Models\Atendimento\Canal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CanalManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_manage_canais_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('atendimento.canais.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_canal(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(CanalManager::class)
            ->set('nome', 'WhatsApp Comercial')
            ->set('tipo', 'whatsapp')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('canais', ['nome' => 'WhatsApp Comercial', 'tipo' => 'whatsapp']);
    }

    public function test_invalid_tipo_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(CanalManager::class)
            ->set('nome', 'Canal Teste')
            ->set('tipo', 'tipo-invalido')
            ->call('save')
            ->assertHasErrors('tipo');
    }

    public function test_can_toggle_ativo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $canal = Canal::create(['nome' => 'Site', 'tipo' => 'site', 'ativo' => true]);

        Livewire::actingAs($admin)
            ->test(CanalManager::class)
            ->call('toggleAtivo', $canal->id);

        $this->assertFalse($canal->fresh()->ativo);
    }
}
