<?php

namespace App\Services\Agente;

use Illuminate\Support\Facades\Http;

class TelegramBotManager
{
    /**
     * @return array{id: int, username: string, first_name: string}
     */
    public function obterInfo(string $token): array
    {
        $resposta = $this->chamar($token, 'getMe');

        return [
            'id' => $resposta['id'],
            'username' => $resposta['username'],
            'first_name' => $resposta['first_name'],
        ];
    }

    /**
     * @return array{nome: string, descricao: string, descricao_curta: string}
     */
    public function obterPerfil(string $token): array
    {
        return [
            'nome' => $this->chamar($token, 'getMyName')['name'] ?? '',
            'descricao' => $this->chamar($token, 'getMyDescription')['description'] ?? '',
            'descricao_curta' => $this->chamar($token, 'getMyShortDescription')['short_description'] ?? '',
        ];
    }

    /**
     * @param  array{nome?: ?string, descricao?: ?string, descricao_curta?: ?string}  $dados
     */
    public function atualizarPerfil(string $token, array $dados): void
    {
        if (array_key_exists('nome', $dados)) {
            $this->executar($token, 'setMyName', ['name' => $dados['nome'] ?: '']);
        }

        if (array_key_exists('descricao', $dados)) {
            $this->executar($token, 'setMyDescription', ['description' => $dados['descricao'] ?: '']);
        }

        if (array_key_exists('descricao_curta', $dados)) {
            $this->executar($token, 'setMyShortDescription', ['short_description' => $dados['descricao_curta'] ?: '']);
        }
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @return array<string, mixed>
     */
    private function chamar(string $token, string $metodo, array $parametros = []): array
    {
        $resultado = $this->executar($token, $metodo, $parametros);

        return is_array($resultado) ? $resultado : [];
    }

    /**
     * @param  array<string, mixed>  $parametros
     */
    private function executar(string $token, string $metodo, array $parametros = []): mixed
    {
        $resposta = Http::asJson()->post("https://api.telegram.org/bot{$token}/{$metodo}", $parametros);

        if ($resposta->failed() || $resposta->json('ok') !== true) {
            throw new \RuntimeException(
                "Falha ao chamar {$metodo} na API do Telegram: ".($resposta->json('description') ?? $resposta->status())
            );
        }

        return $resposta->json('result');
    }
}
