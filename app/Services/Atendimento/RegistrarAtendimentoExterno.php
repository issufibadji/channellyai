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
        Agente $agente,
        array $cliente,
        string $canal,
        ?string $resumo,
        ?string $status,
        array $mensagens,
    ): Atendimento {
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

        $atendimento = Atendimento::withoutGlobalScope('estabelecimento')
            ->where('estabelecimento_id', $estabelecimentoId)
            ->where('cliente_id', $clienteModel->id)
            ->where('canal_id', $canalModel->id)
            ->where('status', '!=', 'resolvido')
            ->latest()
            ->first();

        if (! $atendimento) {
            $atendimento = Atendimento::create([
                'estabelecimento_id' => $estabelecimentoId,
                'cliente_id' => $clienteModel->id,
                'canal_id' => $canalModel->id,
                'status' => $status ?: 'aberto',
                'origem' => 'agente',
                'resumo' => $resumo,
            ]);
        } else {
            $atendimento->update(array_filter([
                'status' => $status,
                'resumo' => $resumo,
            ], fn ($valor) => $valor !== null));
        }

        $atendimento->mensagens()->createMany($mensagens);

        return $atendimento;
    }
}
