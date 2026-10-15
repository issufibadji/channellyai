# 08 — Webhook de entrada: NanoClaw → ChannellyAI

Este documento descreve o endpoint pronto no ChannellyAI para **receber** conversas do NanoClaw. O envio de verdade **não vem do motor NanoClaw em si** (ele não tem webhook de saída nativo) — vem de uma bridge própria, `channellyai-bridge.py`, rodando como serviço systemd na mesma VPS do NanoClaw, que faz polling das mailboxes SQLite de cada agent group e chama esse endpoint. Ver "Como funciona de verdade", mais abaixo.

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

## Como funciona de verdade

`~/channellyai-bridge/bridge.py` (VPS, serviço `channellyai-bridge.service`, fora deste repositório) faz polling periódico das mailboxes SQLite de cada agent group (`~/nanoclaw/groups/<folder>/data/v2-sessions/.../outbound.db`) e chama este webhook pra cada mensagem nova, usando um arquivo de configuração próprio:

```json
// ~/channellyai-bridge/config.json
{
  "groups": {
    "<agent_group_id>": {
      "channellyai_url": "https://.../",
      "webhook_token": "<webhook_token do Agente, copiado de 'IA e Chatbot'>",
      "canais_validos": ["whatsapp", "telegram", "instagram", "facebook", "site", "email"]
    }
  }
}
```

**Toda vez que um agente novo é criado e pareado** (ver `doc/09-automacao-bots-telegram.md`), é preciso adicionar a entrada dele aqui manualmente — isso **não está automatizado ainda** — e reiniciar o serviço (`systemctl --user restart channellyai-bridge.service`). Esquecer esse passo é indistinguível de "o agente está pareado mas nada aparece no ChannellyAI": o bot responde no Telegram normalmente, só não gera `Atendimento` nenhum.

**Cuidado ao copiar o `webhook_token`**: a fonte monoespaçada do terminal confunde fácil `5`/`S`, `0`/`O` e `j`/`J` — um erro de transcrição aqui dá `401` silencioso na bridge (`journalctl --user -u channellyai-bridge.service`), sem nenhum aviso visível no Telegram nem no ChannellyAI. Prefira copiar o valor por seleção de texto (nunca digitar de olho), e, se desconfiar, comparar byte a byte (`ord(c)` em Python) em vez de olhar a string.

## O que falta para fechar a integração de verdade

- Automatizar a adição de uma entrada nova em `config.json` da bridge quando um agente é pareado (hoje é manual — ver acima) — provavelmente um novo endpoint no control server (`doc/09`), espelhando o padrão já usado pros outros passos.
- Nenhum teste automatizado cobre a bridge em si (ela vive fora deste repositório) — só testes manuais em produção.
