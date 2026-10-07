<?php

namespace Tests\Feature\Estabelecimento;

use App\Livewire\Atendimento\AtendimentoManager;
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

class IsolamentoTest extends TestCase
{
    use RefreshDatabase, WithEstabelecimentoAtual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_usuario_do_estabelecimento_a_nao_lista_clientes_do_b(): void
    {
        $estA = $this->criarEstabelecimento(['slug' => 'estabelecimento-a']);
        $estB = $this->criarEstabelecimento(['slug' => 'estabelecimento-b']);

        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $this->vincularUsuario($operator, $estA);

        $this->definirEstabelecimentoAtual($estB);
        Cliente::create(['nome' => 'Cliente B']);

        $this->definirEstabelecimentoAtual($estA);
        Cliente::create(['nome' => 'Cliente A']);

        Livewire::actingAs($operator)
            ->test(ClienteManager::class)
            ->assertSee('Cliente A')
            ->assertDontSee('Cliente B');
    }

    public function test_usuario_nao_abre_atendimento_do_outro_estabelecimento_por_url(): void
    {
        $estA = $this->criarEstabelecimento(['slug' => 'estabelecimento-a']);
        $estB = $this->criarEstabelecimento(['slug' => 'estabelecimento-b']);

        $operatorA = User::factory()->create();
        $operatorA->assignRole('operator');
        $this->vincularUsuario($operatorA, $estA);

        $this->definirEstabelecimentoAtual($estB);
        $clienteB = Cliente::create(['nome' => 'Cliente B']);
        $canalB = Canal::create(['nome' => 'WhatsApp B', 'tipo' => 'whatsapp']);
        $atendimentoB = Atendimento::create([
            'cliente_id' => $clienteB->id,
            'canal_id' => $canalB->id,
            'status' => 'aberto',
        ]);

        $this->actingAs($operatorA)
            ->get(route('atendimento.show', $atendimentoB))
            ->assertNotFound();
    }

    public function test_usuario_nao_edita_canal_do_outro_estabelecimento(): void
    {
        $estA = $this->criarEstabelecimento(['slug' => 'estabelecimento-a']);
        $estB = $this->criarEstabelecimento(['slug' => 'estabelecimento-b']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->definirEstabelecimentoAtual($estB);
        $canalB = Canal::create(['nome' => 'Canal B', 'tipo' => 'site']);

        $this->definirEstabelecimentoAtual($estA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($admin)
            ->test(CanalManager::class)
            ->call('edit', $canalB->id);
    }

    public function test_usuario_nao_apaga_canal_do_outro_estabelecimento(): void
    {
        $estA = $this->criarEstabelecimento(['slug' => 'estabelecimento-a']);
        $estB = $this->criarEstabelecimento(['slug' => 'estabelecimento-b']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->definirEstabelecimentoAtual($estB);
        $canalB = Canal::create(['nome' => 'Canal B', 'tipo' => 'site']);

        $this->definirEstabelecimentoAtual($estA);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($admin)
            ->test(CanalManager::class)
            ->call('delete', $canalB->id);
    }

    public function test_lista_de_atendimentos_nao_mostra_registros_de_outro_estabelecimento(): void
    {
        $estA = $this->criarEstabelecimento(['slug' => 'estabelecimento-a']);
        $estB = $this->criarEstabelecimento(['slug' => 'estabelecimento-b']);

        $this->definirEstabelecimentoAtual($estB);
        $clienteB = Cliente::create(['nome' => 'Cliente B']);
        $canalB = Canal::create(['nome' => 'Canal B', 'tipo' => 'site']);
        Atendimento::create(['cliente_id' => $clienteB->id, 'canal_id' => $canalB->id, 'status' => 'aberto']);

        $this->definirEstabelecimentoAtual($estA);
        $clienteA = Cliente::create(['nome' => 'Cliente A']);
        $canalA = Canal::create(['nome' => 'Canal A', 'tipo' => 'site']);
        Atendimento::create(['cliente_id' => $clienteA->id, 'canal_id' => $canalA->id, 'status' => 'aberto']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AtendimentoManager::class)
            ->assertViewHas('atendimentos', fn ($atendimentos) => $atendimentos->total() === 1);
    }
}
