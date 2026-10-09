<?php

namespace Tests\Unit\Agente;

use App\Services\Agente\CanalConexaoProviderFactory;
use App\Services\Agente\TelegramCanalProvider;
use Tests\TestCase;

class CanalConexaoProviderFactoryTest extends TestCase
{
    public function test_retorna_o_provider_do_telegram(): void
    {
        $provider = (new CanalConexaoProviderFactory)->para('telegram');

        $this->assertInstanceOf(TelegramCanalProvider::class, $provider);
    }

    public function test_retorna_null_para_tipo_ainda_nao_suportado(): void
    {
        $this->assertNull((new CanalConexaoProviderFactory)->para('whatsapp'));
        $this->assertNull((new CanalConexaoProviderFactory)->para('instagram'));
        $this->assertNull((new CanalConexaoProviderFactory)->para('facebook'));
    }
}
