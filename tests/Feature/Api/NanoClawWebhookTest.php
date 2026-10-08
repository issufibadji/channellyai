<?php

namespace Tests\Feature\Api;

use App\Models\Agente;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithEstabelecimentoAtual;
use Tests\TestCase;

class NanoClawWebhookTest extends TestCase
{
    use RefreshDatabase, WithEstabelecimentoAtual;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'cliente' => ['nome' => 'Marcos', 'telefone' => '21996466464'],
            'canal' => 'telegram',
            'status' => 'resolvido',
            'resumo' => 'Cliente remarcou corte de hoje 16h para sexta 11h.',
            'mensagens' => [
                ['remetente' => 'cliente', 'conteudo' => 'Muda meu horário de hoje para sexta às 11h'],
                ['remetente' => 'ia', 'conteudo' => 'Remarcado! Corte masculino agora é sexta-feira, às 11h.'],
            ],
        ], $overrides);
    }

    private function criarAgentePiloto(): Agente
    {
        $estabelecimento = $this->criarEstabelecimento(['nome' => 'Barbearia Piloto', 'slug' => 'barbearia-piloto']);

        return Agente::create([
            'estabelecimento_id' => $estabelecimento->id,
            'nome' => 'Agente Piloto',
            'template' => 'barbearia-base',
        ]);
    }

    public function test_sem_header_de_token_retorna_401(): void
    {
        $this->postJson(route('webhooks.nanoclaw'), $this->payload())
            ->assertUnauthorized();
    }

    public function test_com_token_errado_retorna_401(): void
    {
        $this->criarAgentePiloto();

        $this->withHeaders(['X-NanoClaw-Token' => 'token-invalido'])
            ->postJson(route('webhooks.nanoclaw'), $this->payload())
            ->assertUnauthorized();
    }

    public function test_payload_invalido_retorna_422(): void
    {
        $agente = $this->criarAgentePiloto();

        $this->withHeaders(['X-NanoClaw-Token' => $agente->webhook_token])
            ->postJson(route('webhooks.nanoclaw'), $this->payload([
                'mensagens' => [['remetente' => 'bot-externo', 'conteudo' => 'oi']],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mensagens.0.remetente']);
    }

    public function test_cria_cliente_canal_atendimento_e_mensagens(): void
    {
        $agente = $this->criarAgentePiloto();

        $response = $this->withHeaders(['X-NanoClaw-Token' => $agente->webhook_token])
            ->postJson(route('webhooks.nanoclaw'), $this->payload());

        $response->assertCreated();

        $this->assertDatabaseHas('clientes', ['nome' => 'Marcos', 'telefone' => '21996466464']);
        $this->assertDatabaseHas('canais', ['tipo' => 'telegram']);
        $this->assertDatabaseHas('atendimentos', ['status' => 'resolvido', 'origem' => 'agente']);
        $this->assertDatabaseHas('atendimento_mensagens', ['remetente' => 'cliente']);
        $this->assertDatabaseHas('atendimento_mensagens', ['remetente' => 'ia']);
    }

    public function test_segunda_chamada_reaproveita_cliente_e_canal_existentes(): void
    {
        $agente = $this->criarAgentePiloto();

        $headers = ['X-NanoClaw-Token' => $agente->webhook_token];

        $this->withHeaders($headers)->postJson(route('webhooks.nanoclaw'), $this->payload());
        $this->withHeaders($headers)->postJson(route('webhooks.nanoclaw'), $this->payload());

        $this->assertSame(1, Cliente::withoutGlobalScope('estabelecimento')->count());
        $this->assertSame(1, Canal::withoutGlobalScope('estabelecimento')->count());
        $this->assertSame(2, Atendimento::withoutGlobalScope('estabelecimento')->count());
    }

    public function test_token_regenerado_invalida_o_token_antigo(): void
    {
        $agente = $this->criarAgentePiloto();
        $tokenAntigo = $agente->webhook_token;

        $agente->regenerarWebhookToken();

        $this->withHeaders(['X-NanoClaw-Token' => $tokenAntigo])
            ->postJson(route('webhooks.nanoclaw'), $this->payload())
            ->assertUnauthorized();

        $this->withHeaders(['X-NanoClaw-Token' => $agente->fresh()->webhook_token])
            ->postJson(route('webhooks.nanoclaw'), $this->payload())
            ->assertCreated();
    }
}
