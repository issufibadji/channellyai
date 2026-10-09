<?php

namespace Tests\Unit\Agente;

use App\Services\Agente\NanoClawProvisionador;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NanoClawProvisionadorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'nanoclaw.control_url' => 'http://vps-teste:8766',
            'nanoclaw.control_token' => 'token-de-teste',
        ]);
    }

    public function test_criar_agent_group_envia_folder_nome_e_timezone_e_extrai_o_id(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame('token-de-teste', $request->header('X-Bridge-Token')[0]);
            $this->assertSame('http://vps-teste:8766/agent-groups', $request->url());
            $this->assertSame('barbearia-piloto', $request->data()['folder']);

            return Http::response(['ok' => true, 'output' => json_encode(['id' => 'ag-123', 'folder' => 'barbearia-piloto'])]);
        });

        $resultado = (new NanoClawProvisionador)->criarAgentGroup('barbearia-piloto', 'Barbearia Piloto');

        $this->assertSame('ag-123', $resultado['id']);
        $this->assertSame('barbearia-piloto', $resultado['folder']);
    }

    public function test_gravar_token_envia_token_e_instance(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame('http://vps-teste:8766/agent-groups/barbearia-piloto/telegram-token', $request->url());
            $this->assertSame('123:ABC', $request->data()['token']);

            return Http::response(['ok' => true, 'key' => 'TELEGRAM_BOT_TOKEN', 'escrito' => true]);
        });

        (new NanoClawProvisionador)->gravarToken('barbearia-piloto', '123:ABC');
    }

    public function test_iniciar_pareamento_retorna_pairing_id_e_codigo(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true, 'pairing_id' => 'abc-123', 'code' => '969975']),
        ]);

        $resultado = (new NanoClawProvisionador)->iniciarPareamento('barbearia-piloto');

        $this->assertSame('abc-123', $resultado['pairing_id']);
        $this->assertSame('969975', $resultado['codigo']);
    }

    public function test_status_pareamento_retorna_status_codigo_e_campos(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true, 'status' => 'success', 'code' => '969975', 'fields' => ['PLATFORM_ID' => '123']]),
        ]);

        $resultado = (new NanoClawProvisionador)->statusPareamento('abc-123');

        $this->assertSame('success', $resultado['status']);
        $this->assertSame(['PLATFORM_ID' => '123'], $resultado['campos']);
    }

    public function test_falha_lanca_excecao_com_a_mensagem_do_control_server(): void
    {
        Http::fake(['*' => Http::response(['ok' => false, 'error' => 'folder invalido'], 422)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('folder invalido');

        (new NanoClawProvisionador)->criarAgentGroup('Folder Inválido!', 'x');
    }
}
