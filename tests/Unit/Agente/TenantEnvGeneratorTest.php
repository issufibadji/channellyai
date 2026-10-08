<?php

namespace Tests\Unit\Agente;

use App\Models\Agente;
use App\Models\Estabelecimento;
use App\Services\Agente\TenantEnvGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantEnvGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_o_tenant_env_no_formato_esperado(): void
    {
        $estabelecimento = Estabelecimento::create(['nome' => 'Barbearia Piloto', 'slug' => 'barbearia-piloto', 'tipo_negocio' => 'barbearia']);
        $agente = Agente::create([
            'estabelecimento_id' => $estabelecimento->id,
            'nome' => 'Agente Piloto',
            'template' => 'barbearia-base',
        ]);

        $tenantEnv = (new TenantEnvGenerator)->gerar($agente);

        $this->assertSame("GROUP_FOLDER=barbearia-piloto\nAGENT_TEMPLATE=barbearia-base\n", $tenantEnv);
    }
}
