# 04 — Domínio: Atendimento com IA (Channelly)

Este documento descreve as funcionalidades de negócio extraídas do material de referência (banners) da Channelly IA — atendimento multicanal com IA. **Não é infraestrutura do Core**: é lógica de domínio e deve viver fora das pastas transversais (`Menu`, `RBAC`, `Notifications` genéricas, etc.), embora consuma essa infraestrutura.

---

## Propósito do Domínio

Centralizar o atendimento ao cliente vindo de múltiplos canais (WhatsApp, Instagram, Facebook, Site/Chat, E-mail) em um único painel, com um assistente de IA que resolve o que for possível sozinho e transfere para atendimento humano quando necessário.

---

## Canais de Atendimento

- WhatsApp
- Telegram
- Instagram
- Facebook
- Site / Chat
- E-mail

Cada mensagem recebida deve ser normalizada para uma entidade única de "Atendimento", independente do canal de origem, permitindo tratamento uniforme no restante do sistema.

---

## Fluxo de Atendimento

```
Identificação → IA & Chatbot → Atendimento Humano (se necessário) → Resolução → Relatórios
```

| Etapa | Responsabilidade |
|---|---|
| Identificação | Identifica o cliente e o canal de origem da mensagem |
| IA & Chatbot | Atendimento automático 24/7 — entende, responde e busca a melhor solução |
| Atendimento Humano | Transição suave para um atendente quando a IA não resolve sozinha |
| Resolução | Problema resolvido com eficiência; feedback do cliente sobre o atendimento |
| Relatórios | Métricas e insights para melhorar os resultados do atendimento |

### O que a IA resolve sozinha

- Marcação de consultas/compromissos e reuniões
- Segunda via de pagamento e boleto
- Envio de informativos e avisos
- Respostas a dúvidas frequentes

### Transferência para setor especializado

Quando a IA não resolve, o atendimento é transferido para o setor correto:

- Vendas
- Suporte
- Financeiro
- Logística
- Outros

---

## Painel de Atendimento

Telas necessárias no painel administrativo:

- **Dashboard** — métricas gerais: total de atendimentos, conversas, resoluções, satisfação, gráfico de atendimentos no período, lista de atendimentos recentes com canal e status (Aberto, Em atendimento, Aguardando, Resolvido)
- **Atendimentos** — listagem e detalhe de cada atendimento, com histórico da conversa e canal de origem
- **Clientes** — cadastro/consulta dos clientes atendidos
- **Canais** — configuração de integração de cada canal (WhatsApp, Instagram, Facebook, Site/Chat, E-mail)
- **IA e Chatbot** — configuração do comportamento da IA (regras de resposta automática, fluxos, transferência para setores)
- **Relatórios** — métricas e insights consolidados por período, canal e setor
- **Configurações** — usa o **App Config** do Core (não duplicar mecanismo de configuração)

---

## Relação com o Core

Este domínio **usa** a infraestrutura do Core, mas não a reimplementa:

| Necessidade | Fornecido por |
|---|---|
| Quem pode acessar cada tela do painel | RBAC do Core (roles/permissions) |
| Itens de menu do painel (Dashboard, Atendimentos, Clientes, etc.) | Menu dinâmico do Core (`menu_side_bars`) |
| Configurações gerais (nome do sistema, chaves de integração, etc.) | App Config do Core (`app_configs`) |
| Avisar um atendente sobre um atendimento transferido | Notificações do Core (in-app / Web Push) |
| Registro de quem fez o quê no painel | Audit Logging do Core |

---

## O que NÃO Pertence a Este Domínio

| O que não vai | Onde vai |
|---|---|
| Autenticação, RBAC, Menu, App Config, Notificações genéricas | Core |
| Layout, componentes Blade compartilhados | Core / `03-ui-system.md` |

---

## Estado Atual

A **infraestrutura do painel está implementada e testada** (models, telas, RBAC, menu, audit), agora com **isolamento por estabelecimento** (ver `07-multi-estabelecimento.md`). As **integrações reais de canal e a publicação do agente para o motor externo (NanoClaw) ainda não** existem.

**Modelagem implementada** (`app/Models/Atendimento/*`): `Cliente`, `Canal`, `Atendimento` (com `estabelecimento_id`, `origem`, `resumo`), `AtendimentoMensagem` — todas auditadas (exceto mensagens, por volume).

**O motor de regras por palavra-chave (`ChatbotEngine`/`ChatbotRegra`) foi removido.** A tela "IA e Chatbot" passou a ser a configuração do Agente do estabelecimento atual (`App\Models\Agente`, `AgenteDadosNegocio`, `AgenteServico`): dados de negócio (tom de voz, endereço, horário, antecedência para agendar, calendarId, profissionais, políticas, encaminhamento humano) e serviços (nome, duração, preço). `App\Services\Agente\NegocioMarkdownGenerator` gera o `negocio.md` a partir desses dados, com botão de pré-visualização. A publicação desse arquivo para o NanoClaw ainda não existe — só o ponto de extensão `App\Contracts\PublicadorDeAgente` está definido.

---

## Funcionalidades Planejadas

### Canais

- [ ] Integração com WhatsApp (API oficial ou provedor) — **pendente**, depende de credenciais/API externa
- [ ] Integração com Instagram — **pendente**
- [ ] Integração com Facebook — **pendente**
- [ ] Widget de chat para o site — **pendente**
- [ ] Integração com E-mail (recebimento e resposta) — **pendente**
- [x] Normalização de mensagens em uma entidade única de Atendimento — implementada internamente (`atendimentos` + `atendimento_mensagens`); hoje a única forma de entrada é a tela de Atendimento (inserção manual), não webhooks reais dos canais acima; campo `origem` (`manual`/`agente`) e `resumo` preparam a tela para receber encaminhamentos do agente no futuro
- [x] Cadastro de Canais (tela CRUD) — tipo, nome, ativo/inativo, isolado por estabelecimento; a `configuracao` (JSON) existe no schema para credenciais de API, mas nenhuma integração a lê ainda

### IA & Chatbot

- [x] Cadastro do Agente de IA por estabelecimento (`Agente`, `AgenteDadosNegocio`, `AgenteServico`) — tela "IA e Chatbot"
- [x] Gerador puro do `negocio.md` (`NegocioMarkdownGenerator`) com pré-visualização na tela
- [x] Publicação do `negocio.md`/`tenant.env` para o motor externo (NanoClaw) — via API REST do GitHub (`GithubPublicadorDeAgente`), botão "Publicar" na tela; pré-requisito manual (criação do agent group na VPS) documentado em `07-multi-estabelecimento.md`
- [ ] Fluxo de marcação de compromissos via IA — depende de integração com sistema de agenda (fora de escopo até então)
- [ ] Fluxo de segunda via de pagamento/boleto via IA — depende de integração com sistema financeiro (fora de escopo até então)
- [ ] Envio de informativos e avisos via IA
- [ ] Respostas automáticas a dúvidas frequentes via o agente real (o motor antigo por palavra-chave foi removido)

### Painel

- [x] Dashboard com métricas reais (total, abertos, em atendimento, resolvidos, satisfação média, tendência de 14 dias, breakdown por status/canal, preview da conversa mais recente)
- [x] Listagem de Atendimentos com filtros por canal/status
- [x] Tela de detalhe do Atendimento (thread de mensagens cliente/IA/atendente, mudança de status/setor, atribuição de atendente)
- [x] Cadastro/consulta de Clientes
- [x] Tela de configuração de Canais
- [x] Tela de configuração da IA e Chatbot (regras)
- [x] Relatórios com filtros por período e breakdown por status/canal/setor
- [ ] Export de relatórios (CSV/PDF) — não implementado

### Integração com o Core (pendências)

- [ ] Notificar atendente via Notificações do Core quando um atendimento é transferido para seu setor (hoje não há esse aviso automático)

---

## Stack de Referência

Mesma stack do restante do projeto:

- **Laravel 13**
- **Livewire 4**
- **Alpine.js**
- **Tailwind CSS**
- **MySQL**
