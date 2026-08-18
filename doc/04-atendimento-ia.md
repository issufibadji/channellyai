# 04 — Domínio: Atendimento com IA (Channelly)

Este documento descreve as funcionalidades de negócio extraídas do material de referência (banners) da Channelly IA — atendimento multicanal com IA. **Não é infraestrutura do Core**: é lógica de domínio e deve viver fora das pastas transversais (`Menu`, `RBAC`, `Notifications` genéricas, etc.), embora consuma essa infraestrutura.

---

## Propósito do Domínio

Centralizar o atendimento ao cliente vindo de múltiplos canais (WhatsApp, Instagram, Facebook, Site/Chat, E-mail) em um único painel, com um assistente de IA que resolve o que for possível sozinho e transfere para atendimento humano quando necessário.

---

## Canais de Atendimento

- WhatsApp
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

## Funcionalidades Planejadas

### Canais

- [ ] Integração com WhatsApp (API oficial ou provedor)
- [ ] Integração com Instagram
- [ ] Integração com Facebook
- [ ] Widget de chat para o site
- [ ] Integração com E-mail (recebimento e resposta)
- [ ] Normalização de mensagens de todos os canais em uma entidade única de Atendimento

### IA & Chatbot

- [ ] Motor de IA para identificação de intenção e resposta automática
- [ ] Regras de transferência para setor humano (Vendas, Suporte, Financeiro, Logística, Outros)
- [ ] Fluxo de marcação de compromissos via IA
- [ ] Fluxo de segunda via de pagamento/boleto via IA
- [ ] Envio de informativos e avisos via IA
- [ ] Respostas a dúvidas frequentes (FAQ) via IA

### Painel

- [ ] Dashboard com métricas reais (atendimentos, conversas, resoluções, satisfação)
- [ ] Listagem de Atendimentos com filtros por canal/status
- [ ] Cadastro/consulta de Clientes
- [ ] Tela de configuração de Canais
- [ ] Tela de configuração da IA e Chatbot
- [ ] Relatórios com filtros por período/canal/setor e export

---

## Stack de Referência

Mesma stack do restante do projeto:

- **Laravel 13**
- **Livewire 4**
- **Alpine.js**
- **Tailwind CSS**
- **MySQL**
