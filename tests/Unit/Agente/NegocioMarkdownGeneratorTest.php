<?php

namespace Tests\Unit\Agente;

use App\Models\Agente;
use App\Models\AgenteDadosNegocio;
use App\Models\AgenteServico;
use App\Models\Estabelecimento;
use App\Services\Agente\NegocioMarkdownGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegocioMarkdownGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_o_markdown_no_formato_esperado(): void
    {
        $estabelecimento = Estabelecimento::create(['nome' => 'Barbearia do Zé', 'slug' => 'barbearia-do-ze', 'tipo_negocio' => 'barbearia']);
        $agente = Agente::create(['estabelecimento_id' => $estabelecimento->id, 'nome' => 'Agente Zé']);

        AgenteDadosNegocio::create([
            'agente_id' => $agente->id,
            'nome_exibicao' => 'Barbearia do Zé',
            'tom_de_voz' => 'descontraído',
            'endereco' => 'Rua das Tesouras, 123',
            'horario_funcionamento' => 'seg a sáb, 9h às 19h',
            'antecedencia_minima' => '2 horas',
            'calendar_id' => 'ze@group.calendar.google.com',
            'profissionais' => 'Zé e João',
            'politicas' => ['Cancelamento até 1h antes', 'Atraso de 15 min cancela o horário'],
            'contato_humano' => '(11) 99999-0000',
            'mensagem_encaminhamento' => 'Vou te transferir para um atendente humano.',
        ]);

        AgenteServico::create(['agente_id' => $agente->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 35]);
        AgenteServico::create(['agente_id' => $agente->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 25]);

        $markdown = (new NegocioMarkdownGenerator)->gerar($agente->fresh(['dadosNegocio', 'servicos']));

        $esperado = <<<'MD'
        ## DADOS DO NEGÓCIO

        - **Nome:** Barbearia do Zé
        - **Tom de voz:** descontraído
        - **Endereço:** Rua das Tesouras, 123
        - **Funcionamento:** seg a sáb, 9h às 19h
        - **Antecedência mínima para agendar:** 2 horas
        - **Google Calendar (calendarId):** ze@group.calendar.google.com

        ### Serviços
        | Serviço | Duração | Preço |
        |---|---|---|
        | Corte | 30 min | R$ 35,00 |
        | Barba | 20 min | R$ 25,00 |

        ### Profissionais
        - Zé e João

        ### Políticas
        - Cancelamento até 1h antes
        - Atraso de 15 min cancela o horário

        ### Encaminhamento humano
        - Contato: (11) 99999-0000
        - Mensagem: "Vou te transferir para um atendente humano."
        MD;

        $this->assertSame($esperado, $markdown);
    }

    public function test_gera_tabela_so_com_cabecalho_quando_nao_ha_servicos(): void
    {
        $estabelecimento = Estabelecimento::create(['nome' => 'Consultoria X', 'slug' => 'consultoria-x', 'tipo_negocio' => 'consultoria']);
        $agente = Agente::create(['estabelecimento_id' => $estabelecimento->id, 'nome' => 'Agente X']);

        AgenteDadosNegocio::create([
            'agente_id' => $agente->id,
            'nome_exibicao' => 'Consultoria X',
            'antecedencia_minima' => '1 dia',
        ]);

        $markdown = (new NegocioMarkdownGenerator)->gerar($agente->fresh(['dadosNegocio', 'servicos']));

        $this->assertStringContainsString("| Serviço | Duração | Preço |\n|---|---|---|\n\n### Profissionais", $markdown);
    }

    public function test_lida_com_acentos_aspas_e_barra_vertical_no_conteudo(): void
    {
        $estabelecimento = Estabelecimento::create(['nome' => 'Açaí & Cia', 'slug' => 'acai-cia', 'tipo_negocio' => 'outro']);
        $agente = Agente::create(['estabelecimento_id' => $estabelecimento->id, 'nome' => 'Agente Açaí']);

        AgenteDadosNegocio::create([
            'agente_id' => $agente->id,
            'nome_exibicao' => 'Açaí & Cia "Point do Açaí"',
            'antecedencia_minima' => '30 min',
            'mensagem_encaminhamento' => 'Diga "aguarde um instante", por favor.',
        ]);

        AgenteServico::create(['agente_id' => $agente->id, 'nome' => 'Açaí 500ml | especial', 'duracao_minutos' => 5, 'preco' => 18.9]);

        $markdown = (new NegocioMarkdownGenerator)->gerar($agente->fresh(['dadosNegocio', 'servicos']));

        $this->assertStringContainsString('- **Nome:** Açaí & Cia "Point do Açaí"', $markdown);
        $this->assertStringContainsString('| Açaí 500ml | especial | 5 min | R$ 18,90 |', $markdown);
        $this->assertStringContainsString('Mensagem: "Diga "aguarde um instante", por favor."', $markdown);
    }
}
