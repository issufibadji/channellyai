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

## Fase 0 — Setup do Projeto ✅ Concluída

Pré-requisito para todo o resto.

- [x] Laravel 13 instalado e rodando
- [x] Livewire 4, Alpine.js e Tailwind CSS configurados (`vite.config.js`, `resources/css/app.css`)
- [x] Spatie Permission instalado
- [x] Estrutura de pastas conforme `senior-architect.md` (`app/Actions`, `app/Services`, `app/Livewire`, etc.)
- [x] `master.blade.php` existente — layout com sidebar recolhível, seções agrupadas, tema claro/escuro (ver `02-core.md`)
- [x] `CoreServiceProvider` existente (registra o Gate global de admin)
- [x] Migrations base já presentes no banco (users, cache, jobs, permission_tables, 2FA, menu_side_bars, audits, app_configs, notifications, push_subscriptions, perfil)

> Nota: este projeto (Laravel 13) não usa `EventServiceProvider`/`RouteServiceProvider` como arquivos separados — rotas são carregadas via `bootstrap/app.php` e eventos são auto-descobertos pela convenção `app/Listeners/*` (ver `02-core.md`).

---

## Fase 1 — Autenticação ✅ Concluída

Depende de: Fase 0.

- [x] Login com email + senha
- [x] Logout
- [x] Recuperação de senha (forgot/reset)
- [x] Email verification
- [x] 2FA via TOTP (Google Authenticator)
- [x] Middleware `check2fa`
- [x] Registro de usuário — decisão de produto: **não implementado**; admin cria usuários pela tela de Usuários
- [x] Testes feature do fluxo de autenticação (`tests/Feature/Auth/*`)

**Critério de conclusão:** ✅ atendido — um usuário consegue logar, ser exigido 2FA quando ativado, recuperar senha e ter o email verificado, tudo coberto por teste automatizado.

---

## Fase 2 — RBAC (Roles & Permissions) ✅ Concluída

Depende de: Fase 1.

- [x] Seeder de roles iniciais (`admin`, `manager`, `operator`)
- [x] Seeder de permissions (padrão `[acao]-[recurso]`, ver `senior-architect.md`)
- [x] Gate definitions em `CoreServiceProvider` (admin tem bypass total via `Gate::before`)
- [x] Middleware `checkPermission`
- [x] Stack de middleware completa nas rotas protegidas: `auth → verified → check2fa → checkPermission`
- [x] `@can` funcionando nas views Blade

**Critério de conclusão:** ✅ atendido — um usuário sem a permissão correta recebe 403.

---

## Fase 3 — UI Compartilhada (Layout, Componentes, Menu) ✅ Concluída

Depende de: Fase 2 (menu filtra por permissão).

- [x] Design tokens do Tailwind aplicados em `app.css` (paleta azul-marinho/ciano, tema claro/escuro validado)
- [x] Componentes Blade: `<x-button>`, `<x-card>`, `<x-alert>`, `<x-modal>`, `<x-badge>`, `<x-table>`
- [x] Flash notifications (success, error, warning, info)
- [x] Model `MenuSideBar` (com coluna `group` para seções visuais na sidebar)
- [x] Componente Livewire `Sidebar` lendo itens do banco, com seções agrupadas (ex.: "Atendimento" vs. "Administração do Sistema") e modo recolhido (ícones only, persistido em `localStorage`)
- [x] Filtro de itens do menu por permissão do usuário autenticado
- [x] Tela admin de CRUD dos itens de menu (ordem, ícone, rota, grupo pai, seção) — valida rota existente e ícone Heroicons válido antes de salvar
- [x] Dashboard inicial com métricas reais do Core (função do usuário, notificações não lidas, membro desde, atividade recente)

**Critério de conclusão:** ✅ atendido.

---

## Fase 4 — App Config ✅ Concluída

Depende de: Fase 2 (tela admin protegida por permissão).

- [x] Model `AppConfig`
- [x] Service `AppConfigService` com cache (`get()` para valor texto, `getMediaUrl()` para mídia)
- [x] Helpers `config_app('chave')` e `config_app_media('chave')`
- [x] Tela admin de edição das configurações gerais, com upload de mídia
- [x] Seeder `AppConfigSeeder` com chaves padrão (`app_name`, `app_logo`, `default_user_avatar`)
- [x] Valores **realmente consumidos** na aplicação: nome do sistema e logo aparecem na sidebar, tela de login e QR code do 2FA; avatar padrão é usado quando o usuário não tem foto

**Critério de conclusão:** ✅ atendido — alterar `app_name`/`app_logo`/`default_user_avatar` na tela admin reflete imediatamente na aplicação, sem deploy.

---

## Fase 5 — Notificações ✅ Concluída

Depende de: Fase 1 (usuário autenticado).

- [x] Model `PushSubscription`
- [x] Classes de Notification do Laravel (canais: database, webhook, web push — ver nota abaixo)
- [x] Componente Livewire de sino de notificações (contador de não lidas, marcar como lida)
- [x] Integração de Web Push no front-end (Service Worker + VAPID keys geradas e configuradas)

> Nota: o canal `mail` do plano original foi substituído por `webhook` na implementação — avaliar se envio por e-mail ainda é necessário para este produto.

**Critério de conclusão:** ✅ atendido.

---

## Fase 6 — Perfil do Usuário ✅ Concluída

Depende de: Fase 1.

- [x] Models `UserProfile` (+ CPF/RG/telefone secundário), `UserAdditionalData`, `UserAddress`
- [x] Tela "Meu Perfil" com seções expansíveis: dados da conta (nome/email/senha/exclusão de conta), dados adicionais, endereços (múltiplos), campos personalizados, segurança (2FA embutido)
- [x] Upload e recorte de avatar (`avatar_path`) — cropper client-side em canvas puro, sem lib externa

**Critério de conclusão:** ✅ atendido.

---

## Fase 7 — Audit Logging ✅ Concluída

Depende de: Fases 1–6 (precisa ter ações reais para auditar).

- [x] Model de auditoria — usa `OwenIt\Auditing\Models\Audit` (pacote `owen-it/laravel-auditing`) em vez de um `AuditLog` próprio; equivalente funcional
- [x] Auditoria automática via trait `Auditable` nos models de domínio (`User`, `AppConfig`, `MenuSideBar`, `Role`, `Permission`, models de Atendimento)
- [x] Eventos de autenticação auditados via Listeners (`app/Listeners/Audit/*`): login, logout, tentativa de login falha (só quando o e-mail existe)
- [x] Interface admin para visualizar logs de auditoria (usuário, IP, URL, valores antes/depois)

> Nota: não há um middleware genérico de auditoria de rota — a cobertura é por model (automática) + eventos específicos (login/logout). Suficiente para o critério de conclusão abaixo; revisar se alguma ação sensível fora desse escopo precisar de auditoria futura.

**Critério de conclusão:** ✅ atendido — login, logout, tentativa de login inválida, e edição de config/role/permission aparecem na interface de auditoria.

---

## Fase 8 — Domínio de Negócio: Atendimento com IA 🟡 Infraestrutura concluída, integrações pendentes

Depende de: Core completo (Fases 0–7). Detalhamento completo em `04-atendimento-ia.md`.

- [x] Entidade única de Atendimento (`atendimentos`, `atendimento_mensagens`) e normalização interna — **integrações reais com WhatsApp/Instagram/Facebook/E-mail ainda não implementadas** (dependem de credenciais/API externas)
- [x] Motor de chatbot **MVP** por regras de palavra-chave (`ChatbotEngine`), com transferência para setor — pensado como stub por trás de uma interface estável, pronto para trocar pelo agente de IA real (nanoclaw) sem alterar o resto do sistema
- [x] Painel: Dashboard (com gráfico de tendência, breakdown por status/canal), Atendimentos (lista + chat), Clientes, Canais, IA e Chatbot (regras), Relatórios
- [x] Integração com Menu, RBAC, App Config e Audit do Core (sem duplicar mecanismos) — Notificações do Core ainda não é usada para avisar atendente sobre transferência (gap, ver abaixo)

**Pendências conhecidas (ver checklist detalhado em `04-atendimento-ia.md`):**

- Integrações reais de canal (WhatsApp/Instagram/Facebook/E-mail) — hoje as mensagens são só inseridas manualmente/simuladas na tela
- Substituir o `ChatbotEngine` por regras pelo agente de IA real (nanoclaw)
- Fluxos de IA que dependem de sistemas externos (agendamento, segunda via de boleto) — fora de escopo até haver integração real
- Notificar atendente via Notificações do Core quando um atendimento é transferido
- Export de relatórios (CSV/PDF)

**Critério de conclusão:** parcialmente atendido — painel funcional com dados reais, mas domínio ainda não está pronto pra produção real de atendimento multicanal (ver Fase 9).

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
