# 10 — Google Calendar do agente: como funciona de verdade, e o incidente de 14/10

Este documento não é sobre o ChannellyAI em si — é sobre uma peça de infraestrutura do NanoClaw que faltava documentar: como o agente real (Barbearia Piloto) acessa o Google Calendar pra agendar, e o que quebrou isso por uma semana.

---

## Como o agente acessa o Google Calendar

As instruções do agente (`~/nanoclaw/groups/<folder>/CLAUDE.md`, seção "Como usar o Google Calendar") dizem: *"As credenciais são injetadas automaticamente pelo gateway. Faça as requisições HTTP direto na API."* O agente nunca lida com token nenhum — ele só chama `googleapis.com/calendar/v3/...` normalmente, usando o `calendarId` de "DADOS DO NEGÓCIO" (o campo que já existe na tela "IA e Chatbot" do ChannellyAI).

**O "gateway" é um projeto de verdade, separado**: [oneCLI](https://ghcr.io/onecli/onecli) — um container Docker rodando ao lado do NanoClaw na mesma VPS (`.env` do NanoClaw: `ONECLI_URL=http://172.17.0.1:10254`, `NANOCLAW_GATEWAY_PROVIDER=onecli`). Ele intercepta as chamadas HTTP de saída do agente pros serviços configurados (Google Calendar, entre outros) e injeta a credencial OAuth certa na hora, sem o agente nunca ver o token. A conexão com cada conta Google é gerenciada por um painel web próprio do oneCLI, acessível em `http://localhost:10254` (só via túnel SSH — não é exposto publicamente):

```
ssh -L 10254:172.17.0.1:10254 nanoclaw@<ip-da-vps>
```

Esse painel (`Connections → Apps → Google Calendar`) mostra as contas Google conectadas, com permissões por operação (`List events`, `Create event`, etc.) e por agente.

## Incidente: agendamento parou de funcionar depois de 7 dias

**Sintoma**: o bot da Barbearia Piloto, que já agendava normalmente, passou a sempre responder "Não consigo acessar a agenda agora" e encaminhar pro telefone humano, pra qualquer tentativa de consulta/agendamento.

**Diagnóstico** (nessa ordem, até achar a causa real):
1. `journalctl` do serviço do NanoClaw não tinha nada sobre calendar/google — o erro não chega a ser logado ali, acontece dentro da execução do agente.
2. A pasta do agent group (`~/nanoclaw/groups/barbearia-piloto`) não tem nenhuma credencial — isso é esperado, elas não vivem ali, vivem no oneCLI.
3. O painel do oneCLI (`Overview → Recent Activity`) mostrou a causa direto: uma chamada real pra `www.googleapis.com/calendar/v3/freeBusy` tinha retornado **401 Unauthorized**, minutos antes.
4. A conta `gptibj@gmail.com` estava conectada no oneCLI desde **07/10/2026**, usando **credenciais OAuth próprias** ("Custom credentials", não as padrão do oneCLI) — um Client ID/Secret criado no projeto Google Cloud `barbearia-piloto-agente`.
5. Causa raiz: a tela de consentimento OAuth desse projeto estava em status **"Testing"**. Enquanto um app OAuth está em "Testing", o Google invalida automaticamente o refresh token dos usuários de teste depois de **7 dias** — e 14/10 é exatamente 7 dias depois de 07/10.

**Importante**: isso não tem nada a ver com os clientes que usam o bot. Só a dona do negócio precisou autorizar o acesso à agenda uma vez; os clientes nunca passam por um login do Google.

## Correção aplicada (14/10/2026)

Publicar o app como "Em produção" remove esse limite de 7 dias (a verificação formal do Google, que é outra etapa, só afeta se aparece um aviso de "app não verificado" na tela de consentimento — não afeta a validade do token). Pra publicar, o Google exigiu, nessa ordem:

1. **Link da Política de Privacidade**, apontando pra um domínio que o app controla (não dava pra usar um link do `claude.ai` — o Google exige um "domínio autorizado"). Criado em `resources/views/legal/barbearia-piloto-privacidade.blade.php`, servido em `/legal/barbearia-piloto-privacidade` (rota pública, sem autenticação, em `routes/web.php`).
2. **Verificação de posse do domínio** (`channellyai-channellyai-app.on1ixb.easypanel.host`) via Google Search Console, método "Tag HTML" — uma meta tag `google-site-verification` adicionada nos dois layouts principais (`components/layouts/guest.blade.php` e `components/layouts/master.blade.php`), pra aparecer em qualquer página pública do app (a raiz `/` redireciona pro dashboard/login, então a tag precisa estar em todo layout pra sobreviver ao redirecionamento).
3. **"Página inicial do aplicativo"** (Homepage URL) preenchida — esse campo, vazio, era o motivo real do botão "Publicar app" continuar desabilitado mesmo depois dos passos 1 e 2 (a mensagem de erro da tela só cita "página de branding" genericamente, não diz qual campo falta — só aparece ao passar o mouse sobre o botão desabilitado).
4. Reconectar a conta `gptibj@gmail.com` no oneCLI pra gerar um refresh token novo, já sob o status "Em produção" (publicar não revive um token já expirado).

Testado em produção: o bot real voltou a consultar disponibilidade e criar eventos no Google Calendar normalmente.

## Se isso acontecer de novo (com outro estabelecimento, por exemplo)

Checklist de diagnóstico, do mais rápido ao mais lento:
1. Painel do oneCLI → `Overview` → `Recent Activity` — olha o código de status da última chamada pro `googleapis.com`. `401` = token inválido/expirado (provavelmente o caso do "Testing" de novo, se for um app novo). `403` = escopo ou permissão insuficiente, caso diferente.
2. Se for `401` num app que ainda está em "Testing": publicar como "Em produção" é a correção definitiva (não é um paliativo), seguindo os 4 passos acima.
3. Esse app Google Cloud e esse domínio verificado já existem agora — um agente **novo** que precise de Google Calendar pode reaproveitar o mesmo projeto/credencial (não precisa repetir a verificação de domínio), só conectar a conta Google da calendar certa dentro do oneCLI.
