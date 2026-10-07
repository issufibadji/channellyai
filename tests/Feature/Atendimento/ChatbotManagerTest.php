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

    public function test_opening_the_screen_creates_an_agente_for_the_current_estabelecimento(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)->test(ChatbotManager::class);

        $this->assertSame(1, Agente::count());
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
            ->call('addServico')
            ->set('servicos.0.nome', 'Corte')
            ->set('servicos.0.duracaoMinutos', 30)
            ->set('servicos.0.preco', '35.00')
            ->call('save')
            ->assertHasNoErrors();

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
            ->call('addServico')
            ->set('servicos.0.nome', 'Corte')
            ->set('servicos.0.duracaoMinutos', 30)
            ->set('servicos.0.preco', '35.00')
            ->call('save');

        $this->assertDatabaseHas('agente_servicos', ['nome' => 'Corte']);

        $component
            ->call('removeServico', 0)
            ->call('save');

        $this->assertDatabaseMissing('agente_servicos', ['nome' => 'Corte']);
    }

    public function test_invalid_servico_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('addServico')
            ->set('servicos.0.nome', '')
            ->set('servicos.0.duracaoMinutos', 0)
            ->set('servicos.0.preco', -1)
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
