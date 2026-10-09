<?php

namespace App\Services\Agente;

use App\Contracts\CanalConexaoProvider;

class TelegramCanalProvider implements CanalConexaoProvider
{
    public function __construct(private readonly TelegramBotManager $bot) {}

    public function conectar(array $credenciais): array
    {
        $token = (string) ($credenciais['token'] ?? '');
        $info = $this->bot->obterInfo($token);

        return [
            'identificador' => '@'.$info['username'],
            'configuracao' => ['token' => $token, 'username' => $info['username']],
        ];
    }

    public function suportaPerfil(): bool
    {
        return true;
    }

    public function obterPerfil(array $configuracao): array
    {
        return $this->bot->obterPerfil((string) $configuracao['token']);
    }

    public function atualizarPerfil(array $configuracao, array $dados): void
    {
        $this->bot->atualizarPerfil((string) $configuracao['token'], $dados);
    }
}
