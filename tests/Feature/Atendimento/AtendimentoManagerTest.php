<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\AtendimentoManager;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AtendimentoManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_view_atendimentos_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('atendimento.index'))->assertForbidden();
    }

    public function test_operator_can_create_an_atendimento_and_is_redirected_to_it(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $cliente = Cliente::create(['nome' => 'Maria']);
        $canal = Canal::create(['nome' => 'WhatsApp', 'tipo' => 'whatsapp']);

        Livewire::actingAs($operator)
            ->test(AtendimentoManager::class)
            ->set('clienteId', $cliente->id)
            ->set('canalId', $canal->id)
            ->call('save')
            ->assertRedirect(route('atendimento.show', 1));

        $this->assertDatabaseHas('atendimentos', [
            'cliente_id' => $cliente->id,
            'canal_id' => $canal->id,
            'status' => 'aberto',
        ]);
    }

    public function test_status_filter_narrows_the_list(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $cliente = Cliente::create(['nome' => 'Maria']);
        $canal = Canal::create(['nome' => 'WhatsApp', 'tipo' => 'whatsapp']);

        Atendimento::create(['cliente_id' => $cliente->id, 'canal_id' => $canal->id, 'status' => 'aberto']);
        Atendimento::create(['cliente_id' => $cliente->id, 'canal_id' => $canal->id, 'status' => 'resolvido']);

        Livewire::actingAs($operator)
            ->test(AtendimentoManager::class)
            ->set('statusFilter', 'resolvido')
            ->assertViewHas('atendimentos', fn ($atendimentos) => $atendimentos->total() === 1);
    }
}
