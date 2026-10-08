<?php

namespace Tests\Unit\Atendimento;

use App\Models\Agente;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;
use App\Models\Estabelecimento;
use App\Services\Atendimento\RegistrarAtendimentoExterno;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarAtendimentoExternoTest extends TestCase
{
    use RefreshDatabase;

    private function criarAgente(): Agente
    {
        $estabelecimento = Estabelecimento::create(['nome' => 'Barbearia Piloto', 'slug' => 'barbearia-piloto', 'tipo_negocio' => 'barbearia']);

        return Agente::create([
            'estabelecimento_id' => $estabelecimento->id,
            'nome' => 'Agente Piloto',
            'group_folder' => 'barbearia-piloto',
            'template' => 'barbearia-base',
        ]);
    }

    public function test_cria_tudo_na_primeira_chamada(): void
    {
        $agente = $this->criarAgente();

        $atendimento = (new RegistrarAtendimentoExterno)->registrar(
            groupFolder: 'barbearia-piloto',
            cliente: ['nome' => 'Marcos', 'telefone' => '21999999999'],
            canal: 'telegram',
            resumo: 'Resumo da conversa.',
            status: 'resolvido',
            mensagens: [
                ['remetente' => 'cliente', 'conteudo' => 'Oi'],
                ['remetente' => 'ia', 'conteudo' => 'Olá! Como posso ajudar?'],
            ],
        );

        $this->assertSame($agente->estabelecimento_id, $atendimento->estabelecimento_id);
        $this->assertSame('resolvido', $atendimento->status);
        $this->assertSame('agente', $atendimento->origem);
        $this->assertSame('Resumo da conversa.', $atendimento->resumo);
        $this->assertSame(2, $atendimento->mensagens()->count());
    }

    public function test_reaproveita_cliente_e_canal_em_chamadas_seguintes(): void
    {
        $this->criarAgente();
        $service = new RegistrarAtendimentoExterno;

        $service->registrar('barbearia-piloto', ['nome' => 'Marcos', 'telefone' => '21999999999'], 'telegram', null, null, [
            ['remetente' => 'cliente', 'conteudo' => 'Oi'],
        ]);
        $service->registrar('barbearia-piloto', ['nome' => 'Marcos', 'telefone' => '21999999999'], 'telegram', null, null, [
            ['remetente' => 'cliente', 'conteudo' => 'De novo'],
        ]);

        $this->assertSame(1, Cliente::withoutGlobalScope('estabelecimento')->count());
        $this->assertSame(1, Canal::withoutGlobalScope('estabelecimento')->count());
        $this->assertSame(2, Atendimento::withoutGlobalScope('estabelecimento')->count());
    }

    public function test_status_default_aberto_quando_nao_informado(): void
    {
        $this->criarAgente();

        $atendimento = (new RegistrarAtendimentoExterno)->registrar(
            'barbearia-piloto', ['nome' => 'Marcos', 'telefone' => null], 'telegram', null, null,
            [['remetente' => 'cliente', 'conteudo' => 'Oi']],
        );

        $this->assertSame('aberto', $atendimento->status);
    }

    public function test_group_folder_inexistente_lanca_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new RegistrarAtendimentoExterno)->registrar(
            'nao-existe', ['nome' => 'Marcos', 'telefone' => null], 'telegram', null, null,
            [['remetente' => 'cliente', 'conteudo' => 'Oi']],
        );
    }
}
