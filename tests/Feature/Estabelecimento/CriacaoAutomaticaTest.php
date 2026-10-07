<?php

namespace Tests\Feature\Estabelecimento;

use App\Livewire\Atendimento\CanalManager;
use App\Livewire\Atendimento\ClienteManager;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithEstabelecimentoAtual;
use Tests\TestCase;

class CriacaoAutomaticaTest extends TestCase
{
    use RefreshDatabase, WithEstabelecimentoAtual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cliente_criado_recebe_o_estabelecimento_atual_automaticamente(): void
    {
        $estabelecimento = $this->criarEstabelecimento();
        $this->definirEstabelecimentoAtual($estabelecimento);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Livewire::actingAs($operator)
            ->test(ClienteManager::class)
            ->set('nome', 'Cliente Novo')
            ->call('save');

        $this->assertDatabaseHas('clientes', [
            'nome' => 'Cliente Novo',
            'estabelecimento_id' => $estabelecimento->id,
        ]);
    }

    public function test_canal_criado_recebe_o_estabelecimento_atual_automaticamente(): void
    {
        $estabelecimento = $this->criarEstabelecimento();
        $this->definirEstabelecimentoAtual($estabelecimento);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(CanalManager::class)
            ->set('nome', 'Canal Novo')
            ->set('tipo', 'whatsapp')
            ->call('save');

        $this->assertDatabaseHas('canais', [
            'nome' => 'Canal Novo',
            'estabelecimento_id' => $estabelecimento->id,
        ]);
    }

    public function test_atendimento_criado_recebe_o_estabelecimento_atual_automaticamente(): void
    {
        $estabelecimento = $this->criarEstabelecimento();
        $this->definirEstabelecimentoAtual($estabelecimento);

        $cliente = Cliente::create(['nome' => 'Maria']);
        $canal = Canal::create(['nome' => 'WhatsApp', 'tipo' => 'whatsapp']);

        $atendimento = Atendimento::create([
            'cliente_id' => $cliente->id,
            'canal_id' => $canal->id,
            'status' => 'aberto',
        ]);

        $this->assertSame($estabelecimento->id, $atendimento->estabelecimento_id);
    }
}
