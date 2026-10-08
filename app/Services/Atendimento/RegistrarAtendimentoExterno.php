<?php

namespace App\Services\Atendimento;

use App\Models\Agente;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;

class RegistrarAtendimentoExterno
{
    /**
     * @param  array{nome: string, telefone: ?string}  $cliente
     * @param  array<int, array{remetente: string, conteudo: string}>  $mensagens
     */
    public function registrar(
        string $groupFolder,
        array $cliente,
        string $canal,
        ?string $resumo,
        ?string $status,
        array $mensagens,
    ): Atendimento {
        $agente = Agente::where('group_folder', $groupFolder)->firstOrFail();
        $estabelecimentoId = $agente->estabelecimento_id;

        $canalModel = Canal::withoutGlobalScope('estabelecimento')
            ->firstOrCreate(
                ['estabelecimento_id' => $estabelecimentoId, 'tipo' => $canal],
                ['nome' => ucfirst($canal).' (agente)', 'ativo' => true],
            );

        $clienteModel = Cliente::withoutGlobalScope('estabelecimento')
            ->where('estabelecimento_id', $estabelecimentoId)
            ->where('telefone', $cliente['telefone'] ?? null)
            ->first();

        if (! $clienteModel) {
            $clienteModel = Cliente::create([
                'estabelecimento_id' => $estabelecimentoId,
                'nome' => $cliente['nome'],
                'telefone' => $cliente['telefone'] ?? null,
            ]);
        }

        $atendimento = Atendimento::create([
            'estabelecimento_id' => $estabelecimentoId,
            'cliente_id' => $clienteModel->id,
            'canal_id' => $canalModel->id,
            'status' => $status ?: 'aberto',
            'origem' => 'agente',
            'resumo' => $resumo,
        ]);

        $atendimento->mensagens()->createMany($mensagens);

        return $atendimento;
    }
}
