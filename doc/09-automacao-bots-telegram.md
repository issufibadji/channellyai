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
| Criar o agent group na VPS (`ncl groups create`) + gravar `TELEGRAM_BOT_TOKEN_<nome>` no `.env` do NanoClaw | SSH/API própria na VPS | Não (não envolve Telegram, só a VPS) | Baixo/médio — mexe em infra de produção de terceiros (o NanoClaw), mas são passos mecânicos já descritos como idempotentes na skill `add-telegram` deles |
| **Pareamento** (`pair-telegram`) — ligar um chat do Telegram específico a um agent group | Código de uso único gerado pelo NanoClaw, que **um humano precisa mandar de volta pelo próprio Telegram** | Não envolve BotFather, mas envolve o dono do negócio | Não é risco, é uma trava de segurança proposital do NanoClaw — **não dá pra automatizar sem humano**, nenhuma automação deveria tentar pular isso |

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
- Ao criar com sucesso: grava `TELEGRAM_BOT_TOKEN_<nome>` no `.env` do NanoClaw e roda `ncl groups create` — ambos mecânicos, sem precisar de humano. Ver **Pareamento**, abaixo, para o passo seguinte (esse sim precisa do dono do negócio).

**Pareamento — o passo que fica com um humano, de propósito**

Lendo o código do NanoClaw direto na VPS (`~/nanoclaw/.claude/skills/add-telegram/SKILL.md` e `~/nanoclaw/setup/pair-telegram.ts`), o fluxo real de configurar um bot é em duas partes distintas, não uma só:

1. **Gravar o token** (`TELEGRAM_BOT_TOKEN` ou `TELEGRAM_BOT_TOKEN_<nome>` no `.env`) — isso sozinho já deixa o bot "instalado", mas ele ainda não sabe *qual conversa do Telegram* pertence a qual agent group.
2. **Pareamento** (`pair-telegram`): o NanoClaw gera um código numérico de uso único; alguém manda esse código, pelo Telegram, na conversa com o próprio bot; só depois disso o NanoClaw associa aquele `platform_id` (o chat) ao agent group. É assim que ele garante que quem está configurando o bot é quem realmente controla aquele chat — a mesma lógica por trás de "confirme seu número por SMS" em qualquer app. Não existe um jeito de pular essa etapa sem abrir mão dessa garantia.

Isso significa que a automação real, viável e sem gambiarra, é: o ChannellyAI (via SSH na bridge) faz os passos 1 e 2 (gravar token + criar agent group) sozinho; dispara o `pair-telegram` por SSH e captura o código que ele gera (o script já emite isso como um bloco de status `PAIR_TELEGRAM_CODE { CODE, ... }`, pensado justamente para ser lido por outro programa); mostra esse código na tela do ChannellyAI pro dono do negócio; e espera a confirmação (`PAIR_TELEGRAM { STATUS: success, ... }`) pra marcar o agente como pareado. Um toque humano, mas zero acesso à VPS fora desse fluxo.

**Fluxo B — só Bot API oficial, com o token que já temos (editar nome/descrição, listar)**
- Chama `api.telegram.org/bot<token>/setMyName` etc. direto — sem envolver o BotFather nem a conta de usuário. É o único fluxo que não exige a credencial sensível do Fluxo A.

No ChannellyAI: um novo `App\Services\Agente\TelegramBotProvisioner` (ou parecido), chamado pela tela "IA e Chatbot" (`ChatbotManager`), que fala HTTP com essa API da bridge — nunca SSH direto da aplicação web, nunca a sessão do Telegram chega perto do ChannellyAI. Sucesso grava `agent_group_id` e um novo campo `telegram_bot_username` no `Agente` (fecha a lacuna já registrada em `doc/07-multi-estabelecimento.md`, seção "Não implementado ainda").

---

## Ordem recomendada de implementação (do mais seguro pro mais arriscado)

1. **Fluxo B (editar) — ✅ implementado, e generalizado pra qualquer canal.** Zero risco novo de credencial, usa só o token que o agente já tem.

   Desenho genérico (não ficou amarrado só ao Telegram): a conexão de bot não vive mais no `Agente` — vive em `canais.configuracao` (JSON criptografado, `cast: encrypted:array`, coluna que já existia e nunca tinha sido usada), já que canal é por `estabelecimento`+`tipo`, igual o canal que o webhook do NanoClaw já cria sozinho (`doc/08-webhook-nanoclaw.md`). Quem sabe conversar com a API de cada tipo de canal é uma implementação de `App\Contracts\CanalConexaoProvider` (`conectar`, `suportaPerfil`, `obterPerfil`, `atualizarPerfil`), resolvida por tipo em `App\Services\Agente\CanalConexaoProviderFactory`. Hoje só `telegram` tem provider (`TelegramCanalProvider`, usando `TelegramBotManager` por baixo); os outros tipos (`whatsapp`, `instagram`, `facebook`, `site`, `email`) aparecem na tela como "Em breve" — adicionar um novo canal de verdade é só escrever uma implementação nova do contrato e registrar na factory, sem mexer na tela "IA e Chatbot".

   Na tela: lista todos os tipos de `Canal::TIPOS`; os que têm provider (`Canal::TIPOS_COM_PROVIDER`) ganham um botão "Conectar" — cole a credencial (no Telegram, o token do `@BotFather`), o ChannellyAI valida (`getMe`) e guarda. Depois de conectado, carrega nome/descrição/descrição curta atuais do bot e permite editar e enviar de volta — só nos tipos cujo provider declara `suportaPerfil()` (hoje só o Telegram). Testado em `tests/Unit/Agente/TelegramBotManagerTest.php`, `tests/Unit/Agente/TelegramCanalProviderTest.php`, `tests/Unit/Agente/CanalConexaoProviderFactoryTest.php` e `tests/Feature/Atendimento/ChatbotManagerTest.php`. Criar/excluir o bot em si continua manual (Fluxo A, abaixo).
2. **Automação de `ncl groups create` + token + pareamento guiado — ✅ implementado.** Só toca a VPS (sem BotFather), nenhuma mudança de código no repositório do NanoClaw (os comandos já existem e são mecânicos).

   **Na VPS**: um novo serviço systemd (`channellyai-control.service`), separado da bridge que já lia mensagens, roda `~/channellyai-bridge/control_server.py` — um servidor HTTP (só biblioteca padrão do Python) autenticado por um token fixo (header `X-Bridge-Token`), expondo:
   - `POST /agent-groups` → `ncl groups create --folder --name --timezone` (idempotente).
   - `POST /agent-groups/<folder>/telegram-token` → grava `TELEGRAM_BOT_TOKEN` (ou `TELEGRAM_BOT_TOKEN_<nome>`) no `.env` do NanoClaw **só se ainda não existir** (nunca sobrescreve um bot já configurado) e reinicia o serviço do NanoClaw quando grava algo novo.
   - `POST /agent-groups/<folder>/pair` → dispara `pnpm exec tsx setup/index.ts --step pair-telegram -- --intent new-agent:<folder>` em background, lê o `stdout` em tempo real procurando os blocos `=== NANOCLAW SETUP: ... === END ===` (formato definido em `setup/status.ts`), e devolve o código assim que ele aparece (não espera a confirmação humana, que pode demorar).
   - `GET /pairings/<id>` → consulta o andamento (`pending`/`success`/`failed`) do processo de pareamento em background.

   **No ChannellyAI**: `App\Services\Agente\NanoClawProvisionador` fala HTTP com esse control server (`config/nanoclaw.php`: `NANOCLAW_CONTROL_URL`, `NANOCLAW_CONTROL_TOKEN`). Na tela "IA e Chatbot", dentro do painel do canal Telegram já conectado, um botão **"Conectar ao NanoClaw"** (`ChatbotManager::iniciarProvisionamento`) cria o agent group, grava o token salvo no `Canal.configuracao` e inicia o pareamento, guardando `agent_group_id`, `pareamento_pairing_id` e `pareamento_codigo` no `Agente`. Enquanto o status for `pending`, a tela mostra o código e usa `wire:poll.3s="verificarPareamento"` pra consultar o control server a cada 3s, até o humano mandar o código pelo Telegram e o status virar `success` — fecha de vez a lacuna do `agent_group_id` ("Não implementado ainda" em `doc/07-multi-estabelecimento.md`). Testado em `tests/Unit/Agente/NanoClawProvisionadorTest.php` e `tests/Feature/Atendimento/ChatbotManagerTest.php` (com `Http::fake` — nenhum teste automatizado toca a VPS real).

   **Risco aceito conscientemente**: o control server escuta em `0.0.0.0:8766` (a aplicação ChannellyAI roda noutro host, via Easypanel, então precisa alcançar a VPS pela internet) — protegido só pelo token forte (mesmo modelo de segurança já usado no webhook do NanoClaw, `doc/08-webhook-nanoclaw.md`). Não foi configurado firewall restringindo por IP de origem porque o Easypanel não garante um IP de saída fixo.
3. **Fluxo A — criar** — só depois dos dois acima provados estáveis. Exige provisionar a conta de usuário dedicada e validar o parsing das respostas do BotFather em ambiente de teste antes de produção.
4. **Fluxo A — regenerar token / excluir** — por último, e com confirmação explícita no UI (o "Regenerar" já existe hoje pro token do **webhook** do ChannellyAI — este seria outro, o token do **bot** no Telegram; não confundir os dois na tela). Exclusão de bot é irreversível — vale considerar nunca automatizar e deixar sempre manual, mesmo depois do resto pronto.

## Riscos a decidir antes de começar a construir (não técnicos, de produto/segurança)

- Quem é o "dono" da conta de usuário do Telegram usada pelo Fluxo A? Precisa ser uma conta só pra isso, com 2FA, cujo acesso (número + sessão salva) é tratado como segredo de produção.
- Excluir um bot do Telegram é definitivo — talvez a decisão de produto seja nunca expor isso na UI, só as outras três operações.
- O passo 2 (agent group + token + pareamento) não exige mudança de código no NanoClaw, mas ainda é SSH de escrita num ambiente de produção de terceiros — qualquer bug no comando enviado pela bridge afeta o NanoClaw de verdade, não só o ChannellyAI. Vale testar cada comando manualmente contra um agent group descartável antes de automatizar.

---

## Dependências entre este plano e os outros documentos

- Fecha a lacuna de `agent_group_id` descrita em `doc/07-multi-estabelecimento.md` ("Não implementado ainda").
- Reaproveita a service HTTP que já roda na VPS para a ingestão de mensagens (`doc/08-webhook-nanoclaw.md`), adicionando endpoints novos nela — não é um serviço novo do zero.
- Não depende, e não é bloqueado por, o caminho de resposta ChannellyAI → Telegram (mensagens do atendente chegando ao cliente), que é um plano separado, ainda não iniciado.
