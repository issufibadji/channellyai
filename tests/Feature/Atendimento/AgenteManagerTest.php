<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\AgenteManager;
use App\Models\Agente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithEstabelecimentoAtual;
use Tests\TestCase;

class AgenteManagerTest extends TestCase
{
    use RefreshDatabase, WithEstabelecimentoAtual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->definirEstabelecimentoAtual($this->criarEstabelecimento());
    }

    public function test_user_without_manage_chatbot_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('atendimento.chatbot.index'))->assertForbidden();
    }

    public function test_admin_without_an_estabelecimento_selected_is_redirected(): void
    {
        session()->forget('current_estabelecimento_id');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('atendimento.chatbot.index'))
            ->assertRedirect(route('atendimento.dashboard'));
    }

    public function test_lista_mostra_os_agentes_do_estabelecimento_atual(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $outro = $this->criarEstabelecimento(['nome' => 'Outro', 'slug' => 'outro-estabelecimento-x']);
        Agente::create(['estabelecimento_id' => $outro->id, 'nome' => 'Agente de outro estabelecimento']);

        $estabelecimentoId = session('current_estabelecimento_id');
        Agente::create(['estabelecimento_id' => $estabelecimentoId, 'nome' => 'Agendamento']);

        Livewire::actingAs($admin)
            ->test(AgenteManager::class)
            ->assertSee('Agendamento')
            ->assertDontSee('Agente de outro estabelecimento');
    }

    public function test_criar_agente_novo_redireciona_para_edicao(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AgenteManager::class)
            ->set('nomeNovoAgente', 'Suporte')
            ->call('criar');

        $this->assertDatabaseHas('agentes', ['nome' => 'Suporte']);
    }

    public function test_criar_dois_agentes_gera_group_folder_diferente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)->test(AgenteManager::class)->set('nomeNovoAgente', 'Agendamento')->call('criar');
        Livewire::actingAs($admin)->test(AgenteManager::class)->set('nomeNovoAgente', 'Suporte')->call('criar');

        $folders = Agente::pluck('group_folder');
        $this->assertSame(2, $folders->unique()->count());
    }

    public function test_excluir_agente_remove_do_banco(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $agente = Agente::create(['estabelecimento_id' => session('current_estabelecimento_id'), 'nome' => 'Agendamento']);

        Livewire::actingAs($admin)
            ->test(AgenteManager::class)
            ->call('excluir', $agente->id)
            ->assertSee('Agente excluído.');

        $this->assertDatabaseMissing('agentes', ['id' => $agente->id]);
    }

    public function test_excluir_agente_de_outro_estabelecimento_falha(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $outro = $this->criarEstabelecimento(['nome' => 'Outro', 'slug' => 'outro-estabelecimento-y']);
        $agenteDeOutro = Agente::create(['estabelecimento_id' => $outro->id, 'nome' => 'Agente de outro']);

        Livewire::actingAs($admin)
            ->test(AgenteManager::class)
            ->call('excluir', $agenteDeOutro->id)
            ->assertStatus(404);

        $this->assertDatabaseHas('agentes', ['id' => $agenteDeOutro->id]);
    }
}
