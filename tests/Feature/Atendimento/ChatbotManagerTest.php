<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\ChatbotManager;
use App\Models\Agente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithEstabelecimentoAtual;
use Tests\TestCase;

class ChatbotManagerTest extends TestCase
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

    public function test_admin_without_an_estabelecimento_selected_is_redirected_instead_of_crashing(): void
    {
        session()->forget('current_estabelecimento_id');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('atendimento.chatbot.index'))
            ->assertRedirect(route('atendimento.dashboard'));

        $this->assertSame(0, Agente::count());
    }

    public function test_opening_the_screen_creates_an_agente_for_the_current_estabelecimento(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)->test(ChatbotManager::class);

        $this->assertSame(1, Agente::count());
    }

    public function test_screen_starts_in_edit_mode_when_theres_no_data_yet(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->assertSet('editando', true);
    }

    public function test_screen_starts_in_view_mode_when_data_already_exists_and_editar_opens_the_form(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('save');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->assertSet('editando', false)
            ->assertSee('Barbearia do Zé')
            ->assertDontSee('wire:submit')
            ->call('editar')
            ->assertSet('editando', true);
    }

    public function test_admin_can_save_business_data_and_servicos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('tomDeVoz', 'descontraído')
            ->set('antecedenciaMinima', '2 horas')
            ->set('servicos', [['id' => null, 'nome' => 'Corte', 'duracaoMinutos' => 30, 'preco' => '35.00']])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('atendimento.dashboard'));

        $this->assertDatabaseHas('agente_dados_negocio', [
            'nome_exibicao' => 'Barbearia do Zé',
            'tom_de_voz' => 'descontraído',
        ]);

        $this->assertDatabaseHas('agente_servicos', [
            'nome' => 'Corte',
            'duracao_minutos' => 30,
        ]);
    }

    public function test_removing_a_servico_deletes_it_on_save(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $component = Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->set('servicos', [['id' => null, 'nome' => 'Corte', 'duracaoMinutos' => 30, 'preco' => '35.00']])
            ->call('save');

        $this->assertDatabaseHas('agente_servicos', ['nome' => 'Corte']);

        $component
            ->set('servicos', [])
            ->call('save');

        $this->assertDatabaseMissing('agente_servicos', ['nome' => 'Corte']);
    }

    public function test_removing_a_politica_and_saving_persists_the_removal(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $component = Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('nomeExibicao', 'Barbearia Delta')
            ->set('antecedenciaMinima', '2 horas')
            ->set('politicas', ['chega na hora'])
            ->call('save');

        $this->assertDatabaseHas('agente_dados_negocio', ['politicas' => json_encode(['chega na hora'])]);

        $component
            ->set('politicas', [])
            ->call('save');

        $this->assertDatabaseHas('agente_dados_negocio', ['politicas' => json_encode([])]);
    }

    public function test_invalid_servico_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->set('servicos', [['id' => null, 'nome' => '', 'duracaoMinutos' => 0, 'preco' => -1]])
            ->call('save')
            ->assertHasErrors(['servicos.0.nome', 'servicos.0.duracaoMinutos', 'servicos.0.preco']);
    }

    public function test_preview_button_shows_the_generated_markdown(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('visualizar')
            ->assertSee('## DADOS DO NEGÓCIO')
            ->assertSee('Barbearia do Zé');
    }
}
