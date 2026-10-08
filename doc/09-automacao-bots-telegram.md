# 09 — Plano: automatizar criar/editar/excluir bots do Telegram a partir do ChannellyAI

Este documento é um **plano**, não uma implementação. Nada aqui foi construído ainda. Ele registra a decisão de dar ao ChannellyAI acesso à administração de bots do Telegram (hoje feita à mão, no BotFather) e ao código/VPS do NanoClaw, e o desenho de como isso seria feito com o menor risco possível.

Contexto: hoje, criar um Agente e clicar em "Publicar" no ChannellyAI (ver `doc/07-multi-estabelecimento.md`) só envia `negocio.md`/`tenant.env` pro repositório de deploy — não cria nada no Telegram nem na VPS do NanoClaw. Quem quer um agente realmente respondendo no Telegram ainda precisa, manualmente: falar com o `@BotFather`, rodar o setup interativo do NanoClaw (`pnpm exec tsx setup/index.ts --step pair-telegram`) e criar o agent group (`ncl groups create`).

---

## Achado-chave que muda o desenho: o BotFather não tem API

Confirmado lendo `~/nanoclaw/setup/channels/telegram-pre-step.ts` na VPS: o próprio NanoClaw, no seu wizard de setup, **pede pro operador colar um `TELEGRAM_BOT_TOKEN` que ele já obteve manualmente** — o NanoClaw nunca cria o bot sozinho, só o usa depois de criado.

Isso é assim porque:
- **Bot API** (`api.telegram.org/bot<token>/...`) só existe **depois** que um bot já tem token — dá pra fazer `setMyName`, `setMyDescription`, `setMyCommands`, `setMyShortDescription`, `deleteWebhook`, `close`/`logOut`, `getMe`, etc. Nenhum desses endpoints cria ou apaga um bot.
- **Criar e deletar bots só existe como conversa com `@BotFather`** (`/newbot`, `/deletebot`, `/token` para regenerar, `/setname`, `/setdescription` também existem lá, redundante com o Bot API acima). O BotFather é, ele mesmo, só mais um bot do Telegram — conversar com ele só é possível por uma **conta de usuário** (não por outro bot), usando a API de cliente do Telegram (MTProto), com bibliotecas como Telethon (Python) ou GramJS (Node) — nunca pelo Bot API.

Ou seja: "dar ao ChannellyAI acesso à API do BotFather" na prática significa **dar a uma automação acesso a uma conta de usuário real do Telegram**, que vai mandar mensagens de chat pro `@BotFather` e ler as respostas. Isso é tecnicamente viável (é como a maioria dos painéis de hospedagem de bot fazem), mas é um tipo de credencial bem mais sensível que um token de bot — é a conta inteira.

---

## O que dá pra automatizar, e com que risco

| Operação | Mecanismo | Precisa de conta de usuário (BotFather)? | Risco |
|---|---|---|---|
| Criar bot novo | `/newbot` via conta de usuário | Sim | Alto — primeira vez que uma automação "fala" como um usuário real |
| Editar nome/descrição/comandos de um bot existente | Bot API (`setMyName`, `setMyDescription`, `setMyCommands`) usando o próprio token do bot | **Não** | Baixo — API oficial, só precisa do token que já temos |
| Regenerar token de um bot existente | `/token` via conta de usuário (ou `/revoke`) | Sim | Médio — invalida o token antigo na hora, qualquer integração usando o antigo para de funcionar |
| Excluir bot | `/deletebot` via conta de usuário | Sim | **Muito alto — irreversível**, perde o `@username` do bot permanentemente |
| Criar o agent group na VPS (`ncl groups create`) + gravar `TELEGRAM_BOT_TOKEN_<nome>` no `.env` do NanoClaw + rodar o pareamento | SSH/API própria na VPS | Não (não envolve Telegram, só a VPS) | Médio — é código/infra de produção de terceiros (o NanoClaw), hoje um wizard **interativo**, não feito pra ser chamado por script |

---

## Arquitetura proposta

Reaproveitar o padrão que já existe e já funciona (`channellyai-bridge.service` na VPS, que hoje só lê mensagens — ver `doc/08-webhook-nanoclaw.md`): ele ganha um módulo novo, **separado do core do NanoClaw** (mesma filosofia de "skill"/zero-touch já usada), que expõe uma API HTTP local autenticada (só o ChannellyAI chama, com um token novo, dedicado a essa finalidade):

```
POST   /bots                 → cria um bot novo (fluxo A, abaixo)
PATCH  /bots/{nome}           → atualiza nome/descrição/comandos (fluxo B)
POST   /bots/{nome}/token     → regenera o token (fluxo B, mas via BotFather)
DELETE /bots/{nome}           → exclui o bot (fluxo A — fica atrás de confirmação dupla)
```

**Fluxo A — envolve a conta de usuário (criar, regenerar token, excluir)**
- Uma sessão MTProto (Telethon ou GramJS) autenticada **uma única vez** com um número de telefone dedicado só a isso (nunca o número pessoal de ninguém da equipe) — a sessão fica salva só na VPS, nunca trafega pro banco do ChannellyAI nem para o repositório de deploy.
- O módulo da bridge envia os comandos (`/newbot`, nome, username, `/deletebot`, etc.) pro `@BotFather` e faz parsing das respostas em texto livre dele (não é uma API estruturada — é texto de chat, então o parser precisa ser defensivo e sinalizar erro claro se o formato mudar ou vier um "flood wait").
- Ao criar com sucesso: grava `TELEGRAM_BOT_TOKEN_<nome>` no `.env` do NanoClaw, roda `ncl groups create` e o passo de pareamento (hoje interativo — precisa virar um script não-interativo dedicado, novo, dentro do repositório do NanoClaw, já que o wizard atual (`setup/index.ts`) não foi feito pra ser dirigido por outro programa).

**Fluxo B — só Bot API oficial, com o token que já temos (editar nome/descrição, listar)**
- Chama `api.telegram.org/bot<token>/setMyName` etc. direto — sem envolver o BotFather nem a conta de usuário. É o único fluxo que não exige a credencial sensível do Fluxo A.

No ChannellyAI: um novo `App\Services\Agente\TelegramBotProvisioner` (ou parecido), chamado pela tela "IA e Chatbot" (`ChatbotManager`), que fala HTTP com essa API da bridge — nunca SSH direto da aplicação web, nunca a sessão do Telegram chega perto do ChannellyAI. Sucesso grava `agent_group_id` e um novo campo `telegram_bot_username` no `Agente` (fecha a lacuna já registrada em `doc/07-multi-estabelecimento.md`, seção "Não implementado ainda").

---

## Ordem recomendada de implementação (do mais seguro pro mais arriscado)

1. **Fluxo B (editar) — ✅ implementado.** Zero risco novo de credencial, usa só o token que o agente já tem. Na tela "IA e Chatbot": cole o token do bot (gerado manualmente no `@BotFather`) uma vez para "conectar"; o ChannellyAI valida via `getMe`, guarda o token criptografado (`agentes.telegram_bot_token`, cast `encrypted`) e o `@username` (`telegram_bot_username`). Depois de conectado, carrega nome/descrição/descrição curta atuais do bot (`getMyName`/`getMyDescription`/`getMyShortDescription`) e permite editar e enviar (`setMyName`/`setMyDescription`/`setMyShortDescription`) — tudo via `App\Services\Agente\TelegramBotManager`, testado em `tests/Unit/Agente/TelegramBotManagerTest.php` e `tests/Feature/Atendimento/ChatbotManagerTest.php`. Criar/excluir o bot em si continua manual (Fluxo A, abaixo).
2. **Automação de `ncl groups create` + wiring do `.env`** — só toca a VPS (sem BotFather), mas precisa de um script não-interativo novo no repositório do NanoClaw (hoje não existe; o wizard é interativo). Esse passo sozinho já elimina a maior dor manual de hoje, sem tocar em conta de usuário do Telegram.
3. **Fluxo A — criar** — só depois dos dois acima provados estáveis. Exige provisionar a conta de usuário dedicada e validar o parsing das respostas do BotFather em ambiente de teste antes de produção.
4. **Fluxo A — regenerar token / excluir** — por último, e com confirmação explícita no UI (o "Regenerar" já existe hoje pro token do **webhook** do ChannellyAI — este seria outro, o token do **bot** no Telegram; não confundir os dois na tela). Exclusão de bot é irreversível — vale considerar nunca automatizar e deixar sempre manual, mesmo depois do resto pronto.

## Riscos a decidir antes de começar a construir (não técnicos, de produto/segurança)

- Quem é o "dono" da conta de usuário do Telegram usada pelo Fluxo A? Precisa ser uma conta só pra isso, com 2FA, cujo acesso (número + sessão salva) é tratado como segredo de produção.
- O NanoClaw é um repositório de terceiros em desenvolvimento ativo — criar um script não-interativo lá (passo 2 acima) é mudança de código nesse outro projeto, fora do alcance do ChannellyAI; precisa ser proposto/aceito lá (seguindo a própria filosofia de "skills" deles, documentada em `docs/customizing.md`).
- Excluir um bot do Telegram é definitivo — talvez a decisão de produto seja nunca expor isso na UI, só as outras três operações.

---

## Dependências entre este plano e os outros documentos

- Fecha a lacuna de `agent_group_id` descrita em `doc/07-multi-estabelecimento.md` ("Não implementado ainda").
- Reaproveita a service HTTP que já roda na VPS para a ingestão de mensagens (`doc/08-webhook-nanoclaw.md`), adicionando endpoints novos nela — não é um serviço novo do zero.
- Não depende, e não é bloqueado por, o caminho de resposta ChannellyAI → Telegram (mensagens do atendente chegando ao cliente), que é um plano separado, ainda não iniciado.
