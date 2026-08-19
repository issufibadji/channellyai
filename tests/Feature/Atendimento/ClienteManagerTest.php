<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\ClienteManager;
use App\Models\Atendimento\Cliente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClienteManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_view_clientes_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('atendimento.clientes.index'))->assertForbidden();
    }

    public function test_operator_can_create_a_cliente(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Livewire::actingAs($operator)
            ->test(ClienteManager::class)
            ->set('nome', 'Maria Souza')
            ->set('email', 'maria@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clientes', ['nome' => 'Maria Souza', 'email' => 'maria@example.com']);
    }

    public function test_cliente_can_be_updated_and_deleted(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $cliente = Cliente::create(['nome' => 'Old Name']);

        Livewire::actingAs($operator)
            ->test(ClienteManager::class)
            ->call('edit', $cliente->id)
            ->set('nome', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('New Name', $cliente->fresh()->nome);

        Livewire::actingAs($operator)
            ->test(ClienteManager::class)
            ->call('delete', $cliente->id);

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }
}
