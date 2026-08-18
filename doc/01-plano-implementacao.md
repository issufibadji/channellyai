# 01 — Plano de Implementação

Este documento define a ordem de execução do projeto. A regra é simples: **o Core é construído e estabilizado primeiro**; a regra de negócio (domínio de Atendimento com IA, `04-atendimento-ia.md`) só começa depois que o Core estiver funcional, porque todo o resto da aplicação depende dele (auth, permissões, menu, config, notificações, perfil, auditoria).

Documentos relacionados:
- `02-core.md` — especificação do Core
- `03-ui-system.md` — stack de UI e convenções (Livewire/Blade/Alpine/Tailwind)
- `04-atendimento-ia.md` — domínio de negócio (Atendimento com IA)
- `05-cutover.md` — checklist de go-live
- `senior-architect.md` — padrões de arquitetura e convenções de código

---

## Visão Geral das Fases

```
Fase 0: Setup do projeto        (base Laravel + stack de UI)
Fase 1: Core — Autenticação     (login, logout, recuperação, 2FA, email verification)
Fase 2: Core — RBAC             (roles, permissions, seeders, gates)
Fase 3: Core — UI compartilhada (layout master, componentes Blade, sidebar/menu)
Fase 4: Core — App Config       (configurações da aplicação)
Fase 5: Core — Notificações     (in-app + Web Push)
Fase 6: Core — Perfil do usuário
Fase 7: Core — Audit Logging
Fase 8: Domínio de Negócio      (Atendimento com IA — 04-atendimento-ia.md)
Fase 9: Go-Live                 (05-cutover.md)
```

Cada fase só deve ser considerada concluída quando o item correspondente estiver testado, não apenas codificado. Migrations já existentes no banco (ver `02-core.md`) reduzem trabalho de algumas fases, mas não substituem a implementação de Model/Service/Livewire/telas.

---

## Fase 0 — Setup do Projeto

Pré-requisito para todo o resto.

- [ ] Laravel 13 instalado e rodando
- [ ] Livewire 4, Alpine.js e Tailwind CSS configurados (`vite.config.js`, `resources/css/app.css`)
- [ ] Spatie Permission instalado
- [ ] Estrutura de pastas conforme `senior-architect.md` (`app/Actions`, `app/Services`, `app/Livewire`, etc.)
- [ ] `master.blade.php` existente (já implementado — ver `02-core.md`)
- [ ] `CoreServiceProvider`, `EventServiceProvider`, `RouteServiceProvider` existentes (já implementados)
- [ ] Migrations base já presentes no banco (users, cache, jobs, permission_tables, 2FA, menu_side_bars, audits, app_configs, notifications, push_subscriptions, perfil)

---

## Fase 1 — Autenticação

Depende de: Fase 0.

- [ ] Login com email + senha
- [ ] Logout
- [ ] Recuperação de senha (forgot/reset)
- [ ] Email verification
- [ ] 2FA via TOTP (Google Authenticator) — colunas já existem em `users` (2FA + confirmação)
- [ ] Middleware `check2fa`
- [ ] Registro de usuário (avaliar se é necessário para este produto — normalmente admin cria usuários)
- [ ] Testes feature do fluxo de autenticação

**Critério de conclusão:** um usuário consegue logar, ser exigido 2FA quando ativado, recuperar senha e ter o email verificado — tudo coberto por teste automatizado.

---

## Fase 2 — RBAC (Roles & Permissions)

Depende de: Fase 1.

- [ ] Seeder de roles iniciais (`admin`, `manager`, `operator`)
- [ ] Seeder de permissions (padrão `[acao]-[recurso]`, ver `senior-architect.md`)
- [ ] Gate definitions em `CoreServiceProvider`
- [ ] Middleware `checkPermission`
- [ ] Stack de middleware completa nas rotas protegidas: `auth → verified → check2fa → checkPermission`
- [ ] `@can` funcionando nas views Blade

**Critério de conclusão:** um usuário sem a permissão correta recebe 403 ao tentar acessar rota ou ação protegida.

---

## Fase 3 — UI Compartilhada (Layout, Componentes, Menu)

Depende de: Fase 2 (menu filtra por permissão).

- [ ] Design tokens do Tailwind aplicados em `app.css` (ver `03-ui-system.md` → Referência Visual)
- [ ] Componentes Blade: `<x-button>`, `<x-card>`, `<x-alert>`, `<x-modal>`, badge, table
- [ ] Flash notifications (success, error, warning, info)
- [ ] Model `MenuSideBar`
- [ ] Componente Livewire `Sidebar` lendo itens do banco (`menu_side_bars`)
- [ ] Filtro de itens do menu por permissão do usuário autenticado
- [ ] Tela admin de CRUD dos itens de menu (ordem, ícone, rota, grupo pai)
- [ ] Dashboard inicial (ainda sem métricas de negócio — placeholder)

**Critério de conclusão:** usuário autenticado vê apenas os itens de menu permitidos para seu role; layout consistente com a referência visual em todas as telas do Core.

---

## Fase 4 — App Config

Depende de: Fase 2 (tela admin protegida por permissão).

- [ ] Model `AppConfig`
- [ ] Service `AppConfigService` com cache
- [ ] Helper/facade para leitura rápida (ex.: `config_app('nome_do_sistema')`)
- [ ] Tela admin de edição das configurações gerais (nome do sistema, logo, cores, flags de feature)

**Critério de conclusão:** alterar uma config na tela admin reflete na aplicação sem precisar de deploy, e o cache é invalidado corretamente.

---

## Fase 5 — Notificações

Depende de: Fase 1 (usuário autenticado).

- [ ] Model `PushSubscription`
- [ ] Classes de Notification do Laravel (canais: database, mail, web push)
- [ ] Componente Livewire de sino de notificações (contador de não lidas, marcar como lida)
- [ ] Integração de Web Push no front-end (Service Worker + VAPID keys)

**Critério de conclusão:** uma notificação de teste aparece no sino em tempo real e, com push ativado, chega mesmo com a aba fechada.

---

## Fase 6 — Perfil do Usuário

Depende de: Fase 1.

- [ ] Models `UserProfile`, `UserAdditionalData`, `UserAddress`
- [ ] Tela "Meu Perfil" (dados pessoais, endereços, avatar)
- [ ] Upload e recorte de avatar (`avatar_path`)

**Critério de conclusão:** usuário edita seus próprios dados de perfil, endereço e avatar, com validação e persistência corretas.

---

## Fase 7 — Audit Logging

Depende de: Fases 1–6 (precisa ter ações reais para auditar).

- [ ] Model `AuditLog`
- [ ] Middleware de auditoria (registra route, user_id, payload, IP)
- [ ] Interface admin para visualizar logs de auditoria

**Critério de conclusão:** ações relevantes (login, alteração de config, edição de permissão, etc.) aparecem na interface de auditoria com usuário, IP e payload.

---

## Fase 8 — Domínio de Negócio: Atendimento com IA

Depende de: Core completo (Fases 0–7). Detalhamento completo em `04-atendimento-ia.md`.

- [ ] Canais (WhatsApp, Instagram, Facebook, Site/Chat, E-mail) — normalização em entidade única de Atendimento
- [ ] Motor de IA & Chatbot com regras de transferência para setor humano
- [ ] Painel: Dashboard, Atendimentos, Clientes, Canais, IA e Chatbot, Relatórios
- [ ] Integração com Menu, RBAC, App Config, Notificações e Audit do Core (sem duplicar mecanismos)

**Critério de conclusão:** ver checklist detalhado em `04-atendimento-ia.md`.

---

## Fase 9 — Go-Live

Depende de: Fase 8 concluída. Checklist completo em `05-cutover.md`.

- [ ] Infraestrutura Core validada em produção
- [ ] Funcionalidades de negócio validadas em produção
- [ ] Qualidade de código, performance e segurança conferidas
- [ ] Deploy executado e smoke test aprovado

---

## Regra de Sequenciamento

1. Nenhuma fase do domínio de negócio (Fase 8) começa antes do Core (Fases 0–7) estar funcional e testado.
2. Dentro do Core, Autenticação e RBAC são bloqueantes para todas as demais fases (2FA e permissões afetam tudo).
3. Menu, App Config, Notificações, Perfil e Audit podem ser desenvolvidos em paralelo entre si, desde que Autenticação e RBAC já estejam prontos.
4. Go-Live (Fase 9) só é avaliado depois que Core e Negócio estiverem completos — não é aceitável ir a produção com Core parcial.
