<?php

namespace Tests\Feature\Estabelecimento;

use App\Livewire\EstabelecimentoSwitcher;
use App\Models\Atendimento\Cliente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithEstabelecimentoAtual;
use Tests\TestCase;

class SwitcherTest extends TestCase
{
    use RefreshDatabase, WithEstabelecimentoAtual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_pode_trocar_de_estabelecimento_e_ve_apenas_os_dados_do_selecionado(): void
    {
        $estA = $this->criarEstabelecimento(['slug' => 'estabelecimento-a']);
        $estB = $this->criarEstabelecimento(['slug' => 'estabelecimento-b']);

        $this->definirEstabelecimentoAtual($estA);
        Cliente::create(['nome' => 'Cliente A']);

        $this->definirEstabelecimentoAtual($estB);
        Cliente::create(['nome' => 'Cliente B']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(EstabelecimentoSwitcher::class)
            ->call('switchTo', $estA->id);

        $this->assertSame($estA->id, session('current_estabelecimento_id'));
    }

    public function test_usuario_com_um_unico_vinculo_entra_direto_nele(): void
    {
        $estabelecimento = $this->criarEstabelecimento();

        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $this->vincularUsuario($operator, $estabelecimento);

        $this->actingAs($operator)->get(route('atendimento.dashboard'))->assertOk();

        $this->assertSame($estabelecimento->id, session('current_estabelecimento_id'));
    }

    public function test_usuario_sem_vinculo_e_redirecionado_para_tela_informativa(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get(route('atendimento.dashboard'))
            ->assertRedirect(route('atendimento.sem-estabelecimento'));
    }

    public function test_usuario_nao_pode_trocar_para_estabelecimento_ao_qual_nao_esta_vinculado(): void
    {
        $estA = $this->criarEstabelecimento(['slug' => 'estabelecimento-a']);
        $estB = $this->criarEstabelecimento(['slug' => 'estabelecimento-b']);

        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $this->vincularUsuario($operator, $estA);

        Livewire::actingAs($operator)
            ->test(EstabelecimentoSwitcher::class)
            ->call('switchTo', $estB->id)
            ->assertForbidden();
    }
}
