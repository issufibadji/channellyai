<?php

namespace App\Services\Atendimento;

use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\ChatbotRegra;
use Illuminate\Support\Str;

/**
 * Motor de resposta automática por palavra-chave (MVP).
 *
 * Ponto de integração para o agente de IA (nanoclaw): quando o agente estiver
 * pronto, troque a implementação injetada aqui — a interface de entrada/saída
 * (mensagem do cliente -> ChatbotResponse) permanece a mesma para o resto da
 * aplicação.
 */
class ChatbotEngine
{
    public function responder(Atendimento $atendimento, string $mensagemCliente): ChatbotResponse
    {
        $texto = Str::lower($mensagemCliente);

        $regra = ChatbotRegra::query()
            ->where('ativo', true)
            ->orderBy('order')
            ->get()
            ->first(fn (ChatbotRegra $regra) => str_contains($texto, Str::lower($regra->gatilho)));

        if (! $regra) {
            return new ChatbotResponse(
                resposta: 'Não encontrei uma resposta automática para isso. Vou transferir você para um atendente.',
                setorTransferencia: 'outros',
            );
        }

        return new ChatbotResponse(
            resposta: $regra->resposta ?: 'Encaminhando para o setor responsável.',
            setorTransferencia: $regra->setor_transferencia,
        );
    }
}
