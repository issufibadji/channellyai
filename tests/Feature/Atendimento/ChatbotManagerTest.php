<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\ChatbotManager;
use App\Models\Agente;
use App\Models\Atendimento\Canal;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\WithEstabelecimentoAtual;
use Tests\TestCase;

class ChatbotManagerTest extends TestCase
{
    use RefreshDatabase, WithEstabelecimentoAtual;

    private \App\Models\Estabelecimento $estabelecimento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->estabelecimento = $this->criarEstabelecimento();
        $this->definirEstabelecimentoAtual($this->estabelecimento);
    }

    private function criarAgente(): Agente
    {
        return Agente::create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'nome' => 'Agente de Teste',
        ]);
    }

    private function conectarCanalDeTeste(Agente $agente): void
    {
        Canal::create([
            'agente_id' => $agente->id,
            'nome' => 'Telegram (agente)',
            'tipo' => 'telegram',
            'ativo' => true,
            'configuracao' => ['token' => 'TOKEN-VALIDO', 'username' => 'barbearia_piloto_bot', 'identificador' => '@barbearia_piloto_bot'],
        ]);
    }

    public function test_user_without_manage_chatbot_gets_403(): void
    {
        $agente = $this->criarAgente();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('atendimento.chatbot.show', $agente))->assertForbidden();
    }

    public function test_opening_another_estabelecimento_agente_gets_404(): void
    {
        $outro = $this->criarEstabelecimento(['nome' => 'Outro', 'slug' => 'outro-estabelecimento']);
        $agenteDeOutroEstabelecimento = Agente::create(['estabelecimento_id' => $outro->id, 'nome' => 'Agente de outro']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agenteDeOutroEstabelecimento])
            ->assertStatus(404);
    }

    public function test_screen_starts_in_edit_mode_when_theres_no_data_yet(): void
    {
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->assertSet('editando', true);
    }

    public function test_screen_starts_in_view_mode_when_data_already_exists_and_editar_opens_the_form(): void
    {
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('save');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->assertSet('editando', false)
            ->assertSee('Barbearia do Zé')
            ->assertDontSee('wire:submit')
            ->call('editar')
            ->assertSet('editando', true);
    }

    public function test_admin_can_save_business_data_and_servicos(): void
    {
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('tomDeVoz', 'descontraído')
            ->set('antecedenciaMinima', '2 horas')
            ->set('servicos', [['id' => null, 'nome' => 'Corte', 'duracaoMinutos' => 30, 'preco' => '35.00']])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('atendimento.chatbot.index'));

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
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $component = Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
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
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $component = Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
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
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->set('servicos', [['id' => null, 'nome' => '', 'duracaoMinutos' => 0, 'preco' => -1]])
            ->call('save')
            ->assertHasErrors(['servicos.0.nome', 'servicos.0.duracaoMinutos', 'servicos.0.preco']);
    }

    public function test_preview_button_shows_the_generated_markdown(): void
    {
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('visualizar')
            ->assertSee('## DADOS DO NEGÓCIO')
            ->assertSee('Barbearia do Zé');
    }

    public function test_publicar_atualiza_status_para_publicado_e_mostra_sucesso(): void
    {
        config([
            'nanoclaw.github_token' => 'token-de-teste',
            'nanoclaw.github_repo' => 'issufibadji/agente-atendimento-deploy',
            'nanoclaw.branch' => 'main',
        ]);

        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response([], 404);
            }

            return Http::response(['commit' => ['sha' => 'abc123']], 201);
        });

        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('save')
            ->call('publicar')
            ->assertSee('Agente publicado com sucesso.');

        $this->assertSame('publicado', $agente->fresh()->status_publicacao);
    }

    public function test_publicar_mostra_erro_quando_a_publicacao_falha(): void
    {
        config([
            'nanoclaw.github_token' => 'token-de-teste',
            'nanoclaw.github_repo' => 'issufibadji/agente-atendimento-deploy',
            'nanoclaw.branch' => 'main',
        ]);

        Http::fake(fn () => Http::response(['message' => 'Bad credentials'], 401));

        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('save')
            ->call('publicar')
            ->assertSee('Falha ao publicar o agente.');

        $this->assertSame('erro', $agente->fresh()->status_publicacao);
    }

    public function test_conectar_canal_telegram_com_token_valido(): void
    {
        Http::fake([
            'api.telegram.org/botTOKEN-VALIDO/getMe' => Http::response([
                'ok' => true,
                'result' => ['id' => 1, 'username' => 'barbearia_piloto_bot', 'first_name' => 'Barbearia Piloto'],
            ]),
            'api.telegram.org/botTOKEN-VALIDO/getMyName' => Http::response(['ok' => true, 'result' => ['name' => 'Barbearia Piloto']]),
            'api.telegram.org/botTOKEN-VALIDO/getMyDescription' => Http::response(['ok' => true, 'result' => ['description' => '']]),
            'api.telegram.org/botTOKEN-VALIDO/getMyShortDescription' => Http::response(['ok' => true, 'result' => ['short_description' => '']]),
        ]);

        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('selecionarCanal', 'telegram')
            ->set('canalCredencialInput', 'TOKEN-VALIDO')
            ->call('conectarCanal')
            ->assertSee('conectado com sucesso')
            ->assertSet('canalPerfilCarregado', true);

        $this->assertSame(
            ['token' => 'TOKEN-VALIDO', 'username' => 'barbearia_piloto_bot', 'identificador' => '@barbearia_piloto_bot'],
            Canal::where('agente_id', $agente->id)->where('tipo', 'telegram')->first()->configuracao,
        );
    }

    public function test_conectar_canal_telegram_com_token_invalido_mostra_erro(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401)]);

        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('selecionarCanal', 'telegram')
            ->set('canalCredencialInput', 'TOKEN-INVALIDO')
            ->call('conectarCanal')
            ->assertSee('Não foi possível conectar');

        $this->assertNull(Canal::where('agente_id', $agente->id)->where('tipo', 'telegram')->first());
    }

    public function test_canal_ainda_nao_suportado_nao_pode_ser_conectado(): void
    {
        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('selecionarCanal', 'whatsapp')
            ->call('conectarCanal')
            ->assertStatus(404);
    }

    public function test_atualizar_perfil_do_canal_chama_a_api_do_telegram(): void
    {
        $agente = $this->criarAgente();
        $this->conectarCanalDeTeste($agente);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Http::fake([
            'api.telegram.org/botTOKEN-VALIDO/getMyName' => Http::response(['ok' => true, 'result' => ['name' => 'Barbearia Piloto']]),
            'api.telegram.org/botTOKEN-VALIDO/getMyDescription' => Http::response(['ok' => true, 'result' => ['description' => '']]),
            'api.telegram.org/botTOKEN-VALIDO/getMyShortDescription' => Http::response(['ok' => true, 'result' => ['short_description' => '']]),
            'api.telegram.org/botTOKEN-VALIDO/setMyName' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/botTOKEN-VALIDO/setMyDescription' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/botTOKEN-VALIDO/setMyShortDescription' => Http::response(['ok' => true, 'result' => true]),
        ]);

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('selecionarCanal', 'telegram')
            ->set('canalNome', 'Novo Nome do Bot')
            ->call('atualizarPerfilCanal')
            ->assertSee('Perfil do canal atualizado.');
    }

    public function test_desconectar_canal_remove_a_configuracao(): void
    {
        $agente = $this->criarAgente();
        $this->conectarCanalDeTeste($agente);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('selecionarCanal', 'telegram')
            ->call('desconectarCanal')
            ->assertSee('Canal desconectado do agente.');

        $this->assertNull(Canal::where('agente_id', $agente->id)->where('tipo', 'telegram')->first()->configuracao);
    }

    public function test_iniciar_provisionamento_cria_grupo_grava_token_e_inicia_pareamento(): void
    {
        config(['nanoclaw.control_url' => 'http://vps-teste:8766', 'nanoclaw.control_token' => 'token-de-teste']);

        $agente = $this->criarAgente();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->set('nomeExibicao', 'Barbearia do Zé')
            ->set('antecedenciaMinima', '2 horas')
            ->call('save');
        $this->conectarCanalDeTeste($agente);

        $instanciasRecebidas = [];

        Http::fake(function (Request $request) use (&$instanciasRecebidas) {
            if (str_contains($request->url(), 'telegram-token') || str_contains($request->url(), '/pair')) {
                $instanciasRecebidas[] = $request->data()['instance'] ?? null;
            }

            if (str_contains($request->url(), '/agent-groups') && ! str_contains($request->url(), 'telegram-token') && ! str_contains($request->url(), 'pair')) {
                return Http::response(['ok' => true, 'output' => json_encode(['id' => 'ag-123'])]);
            }
            if (str_contains($request->url(), 'telegram-token')) {
                return Http::response(['ok' => true, 'key' => 'TELEGRAM_BOT_TOKEN', 'escrito' => true]);
            }
            if (str_contains($request->url(), '/pair')) {
                return Http::response(['ok' => true, 'pairing_id' => 'pairing-abc', 'code' => '969975']);
            }

            return Http::response(['ok' => false], 404);
        });

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('selecionarCanal', 'telegram')
            ->call('iniciarProvisionamento')
            ->assertSee('969975');

        $agente->refresh();
        $this->assertSame('ag-123', $agente->agent_group_id);
        $this->assertSame([$agente->group_folder, $agente->group_folder], $instanciasRecebidas);
        $this->assertSame('pairing-abc', $agente->pareamento_pairing_id);
        $this->assertSame('969975', $agente->pareamento_codigo);
        $this->assertSame('pending', $agente->pareamento_status);
    }

    public function test_verificar_pareamento_marca_sucesso(): void
    {
        config(['nanoclaw.control_url' => 'http://vps-teste:8766', 'nanoclaw.control_token' => 'token-de-teste']);

        $agente = $this->criarAgente();
        $agente->update([
            'pareamento_pairing_id' => 'pairing-abc',
            'pareamento_status' => 'pending',
            'pareamento_codigo' => '969975',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Http::fake(['*' => Http::response(['ok' => true, 'status' => 'success', 'code' => '969975', 'fields' => []])]);

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('verificarPareamento')
            ->assertSee('Agente pareado com o NanoClaw');

        $agente->refresh();
        $this->assertSame('success', $agente->pareamento_status);
        $this->assertNotNull($agente->pareado_em);
    }

    public function test_verificar_pareamento_preenche_o_codigo_quando_ele_chega_depois(): void
    {
        config(['nanoclaw.control_url' => 'http://vps-teste:8766', 'nanoclaw.control_token' => 'token-de-teste']);

        $agente = $this->criarAgente();
        $agente->update([
            'pareamento_pairing_id' => 'pairing-abc',
            'pareamento_status' => 'pending',
            'pareamento_codigo' => null,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Http::fake(['*' => Http::response(['ok' => true, 'status' => 'pending', 'code' => '969975', 'fields' => []])]);

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('verificarPareamento');

        $this->assertSame('969975', $agente->fresh()->pareamento_codigo);
    }

    public function test_verificar_pareamento_cria_o_wiring_na_primeira_vez_que_vira_sucesso(): void
    {
        config(['nanoclaw.control_url' => 'http://vps-teste:8766', 'nanoclaw.control_token' => 'token-de-teste']);

        $agente = $this->criarAgente();
        $agente->update([
            'agent_group_id' => 'ag-123',
            'pareamento_pairing_id' => 'pairing-abc',
            'pareamento_status' => 'pending',
            'pareamento_codigo' => '969975',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $chamadasWiring = [];

        Http::fake(function (Request $request) use (&$chamadasWiring, $agente) {
            if (str_contains($request->url(), '/wiring')) {
                $chamadasWiring[] = $request->data();

                return Http::response(['ok' => true, 'output' => '']);
            }

            return Http::response([
                'ok' => true,
                'status' => 'success',
                'code' => '969975',
                'fields' => ['PLATFORM_ID' => 'telegram:5305430737', 'INSTANCE' => 'telegram-'.$agente->group_folder],
            ]);
        });

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('verificarPareamento');

        $this->assertCount(1, $chamadasWiring);
        $this->assertSame('ag-123', $chamadasWiring[0]['agent_group_id']);
        $this->assertSame('telegram:5305430737', $chamadasWiring[0]['platform_id']);
    }

    public function test_verificar_pareamento_nao_repete_o_wiring_se_ja_estava_pareado(): void
    {
        config(['nanoclaw.control_url' => 'http://vps-teste:8766', 'nanoclaw.control_token' => 'token-de-teste']);

        $agente = $this->criarAgente();
        $agente->update([
            'agent_group_id' => 'ag-123',
            'pareamento_pairing_id' => 'pairing-abc',
            'pareamento_status' => 'success',
            'pareamento_codigo' => '969975',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $chamadasWiring = [];

        Http::fake(function (Request $request) use (&$chamadasWiring, $agente) {
            if (str_contains($request->url(), '/wiring')) {
                $chamadasWiring[] = $request->data();

                return Http::response(['ok' => true, 'output' => '']);
            }

            return Http::response([
                'ok' => true,
                'status' => 'success',
                'code' => '969975',
                'fields' => ['PLATFORM_ID' => 'telegram:5305430737', 'INSTANCE' => 'telegram-'.$agente->group_folder],
            ]);
        });

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class, ['agente' => $agente])
            ->call('verificarPareamento');

        $this->assertCount(0, $chamadasWiring);
    }
}
