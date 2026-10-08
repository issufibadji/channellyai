# 08 — Webhook de entrada: NanoClaw → ChannellyAI

Este documento descreve o endpoint pronto no ChannellyAI para **receber** conversas do NanoClaw. **O lado de enviar (dentro do motor NanoClaw) ainda não existe** — o código do motor vive em `github.com/nanocoai/nanoclaw`, fora deste repositório, e não há hoje nenhum webhook de saída lá. Este documento é o contrato que quem for implementar esse envio deve seguir.

---

## Endpoint

```
POST /api/webhooks/nanoclaw
Header: X-NanoClaw-Secret: <NANOCLAW_WEBHOOK_SECRET>
Content-Type: application/json
```

Autenticação: um segredo compartilhado simples (`NANOCLAW_WEBHOOK_SECRET` no `.env`), comparado com `hash_equals()` no middleware `App\Http\Middleware\VerifyNanoClawWebhookSecret`. Sem o header, ou com valor errado, ou sem o segredo configurado no ChannellyAI: `401`.

## Payload

```json
{
  "group_folder": "barbearia-piloto",
  "cliente": { "nome": "Marcos", "telefone": "21996466464" },
  "canal": "telegram",
  "status": "resolvido",
  "resumo": "Cliente remarcou corte de hoje 16h para sexta 11h.",
  "mensagens": [
    { "remetente": "cliente", "conteudo": "Muda meu horário de hoje para sexta às 11h" },
    { "remetente": "ia", "conteudo": "Remarcado! Corte masculino agora é sexta-feira, 9 de outubro, às 11h." }
  ]
}
```

| Campo | Obrigatório | Observação |
|---|---|---|
| `group_folder` | sim | Precisa bater com `agentes.group_folder` de um agente já cadastrado no ChannellyAI — é assim que o estabelecimento é identificado. Sem bater: `404`. |
| `cliente.nome` | sim | |
| `cliente.telefone` | não | Usado para não duplicar o Cliente entre chamadas — sem telefone, cada chamada cria um Cliente novo. |
| `canal` | sim | Precisa estar em `App\Models\Atendimento\Canal::TIPOS` (hoje: `whatsapp`, `telegram`, `instagram`, `facebook`, `site`, `email`). |
| `status` | não | Precisa estar em `App\Models\Atendimento\Atendimento::STATUSES` (`aberto`, `em_atendimento`, `aguardando`, `resolvido`). Default: `aberto`. |
| `resumo` | não | Texto livre. |
| `mensagens` | sim, mínimo 1 | Cada item precisa ter `remetente` (`cliente`, `ia` ou `atendente`) e `conteudo`. |

Payload inválido: `422`, com os erros de validação padrão do Laravel.

## Comportamento

Cada chamada registra **um `Atendimento` novo** (`origem = 'agente'`), com todas as `mensagens` do payload anexadas de uma vez — o modelo é "a conversa aconteceu/terminou, me avise", não sincronização incremental mensagem a mensagem (evita precisar de um campo de id externo pra deduplicar mensagens).

Reaproveitamento entre chamadas:
- **Canal**: reaproveita o canal do estabelecimento com aquele `tipo` se já existir; cria se não.
- **Cliente**: reaproveita por `telefone` dentro do estabelecimento; cria se não existir.
- **Atendimento**: sempre cria um novo a cada chamada.

Resposta de sucesso: `201 { "atendimento_id": <int> }`.

## Implementação no ChannellyAI

- `routes/api.php` — rota, sem sessão/CSRF (grupo `api` padrão do Laravel).
- `App\Http\Middleware\VerifyNanoClawWebhookSecret` — autenticação por segredo.
- `App\Http\Controllers\Api\NanoClawWebhookController` — validação do payload.
- `App\Services\Atendimento\RegistrarAtendimentoExterno` — lógica de criar/reaproveitar Cliente/Canal/Atendimento, testável isoladamente (`tests/Unit/Atendimento/RegistrarAtendimentoExternoTest.php`).

Importante: como não há "estabelecimento atual" de sessão numa chamada de API, o service contorna explicitamente o global scope de `App\Concerns\BelongsToEstabelecimento` (`Model::withoutGlobalScope('estabelecimento')`) e define `estabelecimento_id` manualmente a partir do `group_folder` do agente.

## O que falta para fechar a integração de verdade

- Implementar, no motor NanoClaw (repositório externo, fora do alcance deste trabalho), o envio desse webhook ao final de cada conversa/atendimento.
- Configurar `NANOCLAW_WEBHOOK_SECRET` tanto no ChannellyAI quanto no NanoClaw (mesmo valor).
- Nenhum teste de integração ponta a ponta foi feito contra o motor real — só simulado via `Http`/requisições de teste neste repositório.
