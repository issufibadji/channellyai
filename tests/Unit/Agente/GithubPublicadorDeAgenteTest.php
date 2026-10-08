<?php

namespace Tests\Unit\Agente;

use App\Models\Agente;
use App\Models\AgenteDadosNegocio;
use App\Models\Estabelecimento;
use App\Services\Agente\GithubPublicadorDeAgente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GithubPublicadorDeAgenteTest extends TestCase
{
    use RefreshDatabase;

    private function criarAgente(): Agente
    {
        $estabelecimento = Estabelecimento::create(['nome' => 'Barbearia Piloto', 'slug' => 'barbearia-piloto', 'tipo_negocio' => 'barbearia']);
        $agente = Agente::create([
            'estabelecimento_id' => $estabelecimento->id,
            'nome' => 'Agente Piloto',
            'template' => 'barbearia-base',
        ]);
        AgenteDadosNegocio::create(['agente_id' => $agente->id, 'nome_exibicao' => 'Barbearia Piloto']);

        return $agente;
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'nanoclaw.github_token' => 'token-de-teste',
            'nanoclaw.github_repo' => 'issufibadji/agente-atendimento-deploy',
            'nanoclaw.branch' => 'main',
        ]);
    }

    public function test_publica_arquivos_novos_sem_sha_e_atualiza_status(): void
    {
        $agente = $this->criarAgente();

        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response([], 404);
            }

            $this->assertSame('Bearer token-de-teste', $request->header('Authorization')[0]);
            $this->assertArrayNotHasKey('sha', $request->data());
            $this->assertSame('main', $request->data()['branch']);

            return Http::response(['commit' => ['sha' => 'abc123']], 201);
        });

        (new GithubPublicadorDeAgente(new \App\Services\Agente\TenantEnvGenerator))
            ->publicar($agente, "## DADOS DO NEGÓCIO\n");

        $agente->refresh();

        $this->assertSame('publicado', $agente->status_publicacao);
        $this->assertNotNull($agente->publicado_em);
        $this->assertSame('abc123', $agente->ultimo_commit);
    }

    public function test_publica_arquivo_existente_enviando_o_sha_atual(): void
    {
        $agente = $this->criarAgente();

        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response(['sha' => 'sha-antigo']);
            }

            $this->assertSame('sha-antigo', $request->data()['sha']);

            return Http::response(['commit' => ['sha' => 'novo-sha']], 200);
        });

        (new GithubPublicadorDeAgente(new \App\Services\Agente\TenantEnvGenerator))
            ->publicar($agente, "## DADOS DO NEGÓCIO\n");

        $this->assertSame('novo-sha', $agente->fresh()->ultimo_commit);
    }

    public function test_envia_o_markdown_correto_em_base64(): void
    {
        $agente = $this->criarAgente();
        $markdown = "## DADOS DO NEGÓCIO\n\n- **Nome:** Barbearia Piloto\n";

        Http::fake(function (Request $request) use ($markdown) {
            if ($request->method() === 'GET') {
                return Http::response([], 404);
            }

            if (str_contains($request->url(), 'negocio.md')) {
                $this->assertSame($markdown, base64_decode($request->data()['content']));
            }

            return Http::response(['commit' => ['sha' => 'abc123']], 201);
        });

        (new GithubPublicadorDeAgente(new \App\Services\Agente\TenantEnvGenerator))
            ->publicar($agente, $markdown);
    }

    public function test_falha_na_publicacao_marca_status_erro_e_relanca(): void
    {
        $agente = $this->criarAgente();

        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response([], 404);
            }

            return Http::response(['message' => 'Bad credentials'], 401);
        });

        $this->expectException(\RuntimeException::class);

        try {
            (new GithubPublicadorDeAgente(new \App\Services\Agente\TenantEnvGenerator))
                ->publicar($agente, "## DADOS DO NEGÓCIO\n");
        } finally {
            $this->assertSame('erro', $agente->fresh()->status_publicacao);
        }
    }
}
