<?php

namespace App\Contracts;

/**
 * Ponto de extensão para conectar um canal (Telegram, WhatsApp, Instagram, Facebook...)
 * a um bot/conta real, por fora do `App\Models\Atendimento\Canal` que já existe.
 *
 * Cada tipo de canal tem seu próprio formato de credencial (um token de bot no
 * Telegram, um par de phone_number_id + access_token no WhatsApp Cloud API,
 * etc.) — por isso `conectar()` e `atualizarPerfil()` recebem/retornam arrays
 * livres (`configuracao`), guardados em `canais.configuracao` (criptografado).
 *
 * Implementações: `App\Services\Agente\TelegramCanalProvider` (único tipo
 * suportado até agora). Novos canais ganham uma implementação nova, registrada
 * em `App\Services\Agente\CanalConexaoProviderFactory` — nenhuma mudança na
 * tela "IA e Chatbot" é necessária além disso (ver `doc/09-automacao-bots-telegram.md`).
 */
interface CanalConexaoProvider
{
    /**
     * Valida a credencial informada pelo usuário e retorna o que deve ser
     * guardado em `canais.configuracao`, mais um identificador curto pra
     * exibir na tela (ex.: o @username do bot). Lança exceção se inválida.
     *
     * @param  array<string, mixed>  $credenciais
     * @return array{identificador: string, configuracao: array<string, mixed>}
     */
    public function conectar(array $credenciais): array;

    /** Se este canal permite ler/editar nome e descrição do bot (hoje só o Telegram). */
    public function suportaPerfil(): bool;

    /**
     * @param  array<string, mixed>  $configuracao
     * @return array{nome: string, descricao: string, descricao_curta: string}
     */
    public function obterPerfil(array $configuracao): array;

    /**
     * @param  array<string, mixed>  $configuracao
     * @param  array{nome?: ?string, descricao?: ?string, descricao_curta?: ?string}  $dados
     */
    public function atualizarPerfil(array $configuracao, array $dados): void;
}
