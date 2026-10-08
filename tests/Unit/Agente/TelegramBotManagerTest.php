<?php

namespace Tests\Unit\Agente;

use App\Services\Agente\TelegramBotManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotManagerTest extends TestCase
{
    public function test_obter_info_retorna_dados_do_bot(): void
    {
        Http::fake([
            'api.telegram.org/bot123:ABC/getMe' => Http::response([
                'ok' => true,
                'result' => ['id' => 999, 'username' => 'barbearia_piloto_bot', 'first_name' => 'Barbearia Piloto'],
            ]),
        ]);

        $info = (new TelegramBotManager)->obterInfo('123:ABC');

        $this->assertSame('barbearia_piloto_bot', $info['username']);
    }

    public function test_obter_info_com_token_invalido_lanca_excecao(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401),
        ]);

        $this->expectException(\RuntimeException::class);

        (new TelegramBotManager)->obterInfo('token-invalido');
    }

    public function test_obter_perfil_le_nome_descricao_e_descricao_curta(): void
    {
        Http::fake(function (Request $request) {
            return match (true) {
                str_contains($request->url(), 'getMyName') => Http::response(['ok' => true, 'result' => ['name' => 'Barbearia Piloto']]),
                str_contains($request->url(), 'getMyDescription') => Http::response(['ok' => true, 'result' => ['description' => 'Agende seu corte aqui.']]),
                str_contains($request->url(), 'getMyShortDescription') => Http::response(['ok' => true, 'result' => ['short_description' => 'Agendamentos']]),
                default => Http::response(['ok' => false], 404),
            };
        });

        $perfil = (new TelegramBotManager)->obterPerfil('123:ABC');

        $this->assertSame('Barbearia Piloto', $perfil['nome']);
        $this->assertSame('Agende seu corte aqui.', $perfil['descricao']);
        $this->assertSame('Agendamentos', $perfil['descricao_curta']);
    }

    public function test_atualizar_perfil_envia_apenas_os_campos_informados(): void
    {
        $chamadas = [];

        Http::fake(function (Request $request) use (&$chamadas) {
            $chamadas[] = $request->url();

            return Http::response(['ok' => true, 'result' => []]);
        });

        (new TelegramBotManager)->atualizarPerfil('123:ABC', ['nome' => 'Novo Nome']);

        $this->assertCount(1, $chamadas);
        $this->assertStringContainsString('setMyName', $chamadas[0]);
    }

    public function test_atualizar_perfil_com_falha_lanca_excecao(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $this->expectException(\RuntimeException::class);

        (new TelegramBotManager)->atualizarPerfil('123:ABC', ['nome' => 'Novo Nome']);
    }
}
