<?php

namespace App\Services\Agente;

use Illuminate\Support\Facades\Http;

/**
 * Fala com o "control server" (Python, stdlib only) que roda na VPS do
 * NanoClaw, ao lado da bridge que já lê mensagens (ver
 * doc/09-automacao-bots-telegram.md). Cria o agent group, grava o token do
 * bot e dispara o pareamento — a confirmação do pareamento em si depende de
 * um humano mandar o código pelo Telegram, então essa classe só inicia e
 * consulta o andamento, nunca completa sozinha.
 */
class NanoClawProvisionador
{
    /**
     * @return array{id: ?string, nome: string, folder: string}
     */
    public function criarAgentGroup(string $folder, string $nome, string $timezone = 'America/Sao_Paulo'): array
    {
        $resposta = $this->chamar('post', '/agent-groups', [
            'folder' => $folder,
            'name' => $nome,
            'timezone' => $timezone,
        ]);

        $id = null;
        if (isset($resposta['output'])) {
            $dados = json_decode((string) $resposta['output'], true);
            $id = $dados['id'] ?? null;
        }

        return ['id' => $id, 'nome' => $nome, 'folder' => $folder];
    }

    public function gravarToken(string $folder, string $token, ?string $instance = null): void
    {
        $this->chamar('post', "/agent-groups/{$folder}/telegram-token", [
            'token' => $token,
            'instance' => $instance,
        ]);
    }

    /**
     * @return array{pairing_id: string, codigo: ?string}
     */
    public function iniciarPareamento(string $folder, ?string $instance = null): array
    {
        $resposta = $this->chamar('post', "/agent-groups/{$folder}/pair", [
            'instance' => $instance,
        ]);

        return ['pairing_id' => $resposta['pairing_id'], 'codigo' => $resposta['code'] ?? null];
    }

    /**
     * @return array{status: string, codigo: ?string, campos: array<string, mixed>}
     */
    public function statusPareamento(string $pairingId): array
    {
        $resposta = $this->chamar('get', "/pairings/{$pairingId}");

        return [
            'status' => $resposta['status'],
            'codigo' => $resposta['code'] ?? null,
            'campos' => $resposta['fields'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function chamar(string $metodo, string $caminho, array $payload = []): array
    {
        $url = rtrim((string) config('nanoclaw.control_url'), '/').$caminho;

        $resposta = Http::withHeaders(['X-Bridge-Token' => config('nanoclaw.control_token')])
            ->{$metodo}($url, $payload);

        if ($resposta->failed() || $resposta->json('ok') !== true) {
            throw new \RuntimeException(
                "Falha ao chamar {$caminho} no control server do NanoClaw: ".($resposta->json('error') ?? $resposta->status())
            );
        }

        return $resposta->json();
    }
}
