# 08 — Webhook de entrada: NanoClaw → ChannellyAI

Este documento descreve o endpoint pronto no ChannellyAI para **receber** conversas do NanoClaw. **O lado de enviar (dentro do motor NanoClaw) ainda não existe** — o código do motor vive em `github.com/nanocoai/nanoclaw`, fora deste repositório, e não há hoje nenhum webhook de saída lá. Este documento é o contrato que quem for implementar esse envio deve seguir.

---

## Endpoint

```
POST /api/webhooks/nanoclaw
Header: X-NanoClaw-Token: <token do agente>
Content-Type: application/json
```

Autenticação: **um token por Agente**, gerado automaticamente na criação (`agentes.webhook_token`, coluna única), visível e regenerável na tela "IA e Chatbot" (botão "Regenerar" — regenerar invalida o token anterior imediatamente). O token identifica sozinho o agente (e, por tabela, o estabelecimento) — o payload não precisa mais informar `group_folder`. Sem o header, ou com um token que não bate com nenhum agente: `401`.

## Payload

```json
{
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
| `cliente.nome` | sim | |
| `cliente.telefone` | não | Usado para não duplicar o Cliente entre chamadas — sem telefone, cada chamada cria um Cliente novo. |
| `canal` | sim | Precisa estar em `App\Models\Atendimento\Canal::TIPOS` (hoje: `whatsapp`, `telegram`, `instagram`, `facebook`, `site`, `email`). |
| `status` | não | Precisa estar em `App\Models\Atendimento\Atendimento::STATUSES` (`aberto`, `em_atendimento`, `aguardando`, `resolvido`). Default: `aberto`. |
| `resumo` | não | Texto livre. |
| `mensagens` | sim, mínimo 1 | Cada item precisa ter `remetente` (`cliente`, `ia` ou `atendente`) e `conteudo`. |

Payload inválido: `422`, com os erros de validação padrão do Laravel.

## Comportamento

Cada chamada anexa as `mensagens` do payload a um `Atendimento` (`origem = 'agente'`) — não é preciso informar um id externo, a dedução de qual atendimento usar é automática (ver abaixo). Não há sincronização incremental mensagem a mensagem: cada chamada manda o lote inteiro de mensagens novas daquele instante.

Reaproveitamento entre chamadas:
- **Canal**: reaproveita o canal do estabelecimento com aquele `tipo` se já existir; cria se não.
- **Cliente**: reaproveita por `telefone` dentro do estabelecimento; cria se não existir.
- **Atendimento**: reaproveita o atendimento mais recente do mesmo cliente+canal **enquanto o status não for `resolvido`** — as mensagens novas são anexadas nele, e `status`/`resumo` do payload (quando enviados) atualizam o registro existente. Só cria um atendimento novo quando não existe nenhum em aberto (primeira conversa, ou a conversa anterior já foi marcada `resolvido`). Isso evita que uma integração que chama o webhook periodicamente (ex.: um polling a cada N segundos) fragmente uma única conversa em vários atendimentos — a conversa só "vira página" quando alguém a marca como resolvida.

Resposta de sucesso: `201 { "atendimento_id": <int> }`.

## Implementação no ChannellyAI

- `routes/api.php` — rota, sem sessão/CSRF (grupo `api` padrão do Laravel).
- `App\Http\Controllers\Api\NanoClawWebhookController` — resolve o `Agente` pelo token do header (`401` se não encontrar) e valida o payload.
- `App\Services\Atendimento\RegistrarAtendimentoExterno` — lógica de criar/reaproveitar Cliente/Canal/Atendimento, testável isoladamente (`tests/Unit/Atendimento/RegistrarAtendimentoExternoTest.php`).
- `App\Models\Agente::$webhook_token` — gerado automaticamente na criação do agente (`Agente::gerarWebhookToken()`), regenerável via `Agente::regenerarWebhookToken()`; excluído do log de auditoria (`$auditExclude`).

Importante: como não há "estabelecimento atual" de sessão numa chamada de API, o service contorna explicitamente o global scope de `App\Concerns\BelongsToEstabelecimento` (`Model::withoutGlobalScope('estabelecimento')`) e define `estabelecimento_id` manualmente a partir do agente resolvido pelo token.

## O que falta para fechar a integração de verdade

- Implementar, no motor NanoClaw (repositório externo, fora do alcance deste trabalho), o envio desse webhook ao final de cada conversa/atendimento, usando o token do agente correspondente.
- Nenhum teste de integração ponta a ponta foi feito contra o motor real — só simulado via requisições de teste neste repositório e via `curl` manual contra o servidor de desenvolvimento.
