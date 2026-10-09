<?php

namespace Tests\Unit\Agente;

use App\Services\Agente\TelegramBotManager;
use App\Services\Agente\TelegramCanalProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramCanalProviderTest extends TestCase
{
    public function test_conectar_retorna_identificador_e_configuracao(): void
    {
        Http::fake([
            'api.telegram.org/botTOKEN-VALIDO/getMe' => Http::response([
                'ok' => true,
                'result' => ['id' => 1, 'username' => 'barbearia_piloto_bot', 'first_name' => 'Barbearia Piloto'],
            ]),
        ]);

        $resultado = (new TelegramCanalProvider(new TelegramBotManager))->conectar(['token' => 'TOKEN-VALIDO']);

        $this->assertSame('@barbearia_piloto_bot', $resultado['identificador']);
        $this->assertSame(['token' => 'TOKEN-VALIDO', 'username' => 'barbearia_piloto_bot'], $resultado['configuracao']);
    }

    public function test_suporta_perfil(): void
    {
        $this->assertTrue((new TelegramCanalProvider(new TelegramBotManager))->suportaPerfil());
    }
}
