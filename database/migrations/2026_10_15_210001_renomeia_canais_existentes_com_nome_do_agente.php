<?php

use App\Models\Atendimento\Canal;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Canal::withoutGlobalScope('estabelecimento')
            ->with('agente.dadosNegocio')
            ->whereNotNull('agente_id')
            ->get()
            ->each(function (Canal $canal) {
                $agente = $canal->agente;

                if (! $agente) {
                    return;
                }

                $nomeAgente = $agente->dadosNegocio?->nome_exibicao ?: $agente->nome;
                $rotulo = Canal::TIPOS[$canal->tipo] ?? ucfirst($canal->tipo);

                // Só renomeia o nome padrão antigo ("Telegram (agente)") — não mexe em
                // canais que o usuário já renomeou manualmente.
                if ($canal->nome === "{$rotulo} (agente)") {
                    $canal->update(['nome' => "{$rotulo} — {$nomeAgente}"]);
                }
            });
    }

    public function down(): void
    {
        // Renomeação de dado — nada a reverter.
    }
};
