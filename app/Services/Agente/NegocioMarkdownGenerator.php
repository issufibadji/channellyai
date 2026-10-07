<?php

namespace App\Services\Agente;

use App\Models\Agente;

class NegocioMarkdownGenerator
{
    public function gerar(Agente $agente): string
    {
        $dados = $agente->dadosNegocio;

        $linhas = [
            '## DADOS DO NEGÓCIO',
            '',
            '- **Nome:** '.($dados?->nome_exibicao ?? ''),
            '- **Tom de voz:** '.($dados?->tom_de_voz ?? ''),
            '- **Endereço:** '.($dados?->endereco ?? ''),
            '- **Funcionamento:** '.($dados?->horario_funcionamento ?? ''),
            '- **Antecedência mínima para agendar:** '.($dados?->antecedencia_minima ?? ''),
            '- **Google Calendar (calendarId):** '.($dados?->calendar_id ?? ''),
            '',
            '### Serviços',
            '| Serviço | Duração | Preço |',
            '|---|---|---|',
        ];

        foreach ($agente->servicos as $servico) {
            $preco = number_format((float) $servico->preco, 2, ',', '.');
            $linhas[] = "| {$servico->nome} | {$servico->duracao_minutos} min | R$ {$preco} |";
        }

        $linhas[] = '';
        $linhas[] = '### Profissionais';
        $linhas[] = '- '.($dados?->profissionais ?? '');

        $linhas[] = '';
        $linhas[] = '### Políticas';

        foreach (($dados?->politicas ?? []) as $politica) {
            $linhas[] = '- '.$politica;
        }

        $linhas[] = '';
        $linhas[] = '### Encaminhamento humano';
        $linhas[] = '- Contato: '.($dados?->contato_humano ?? '');
        $linhas[] = '- Mensagem: "'.($dados?->mensagem_encaminhamento ?? '').'"';

        return implode("\n", $linhas);
    }
}
