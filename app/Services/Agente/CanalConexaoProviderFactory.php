<?php

namespace App\Services\Agente;

use App\Contracts\CanalConexaoProvider;

class CanalConexaoProviderFactory
{
    /** Null quando o tipo ainda não tem provider implementado (ver Canal::TIPOS_COM_PROVIDER). */
    public function para(string $tipo): ?CanalConexaoProvider
    {
        return match ($tipo) {
            'telegram' => app(TelegramCanalProvider::class),
            default => null,
        };
    }
}
