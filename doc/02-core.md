# 02 — Core da Aplicação

O Core é a infraestrutura transversal da aplicação. Ele provê autenticação, RBAC, layouts compartilhados, sidebar de navegação e primitivas de UI para as demais áreas do sistema. Não contém lógica de negócio de nenhum domínio.

---

## Propósito do Core

O Core existe para resolver problemas que **todas as áreas da aplicação têm em comum**:

- Quem é o usuário autenticado?
- Quais permissões ele tem?
- Como o layout da aplicação é estruturado?
- Onde ficam os componentes Blade compartilhados (botões, cards, alertas)?
- Como registrar eventos de auditoria?

Tudo isso é responsabilidade do Core. O que **não é** responsabilidade do Core: qualquer lógica relacionada a agendamentos, pagamentos, relatórios ou qualquer outro domínio de negócio.

---

## Estado Atual

O Core está **implementado e testado** (Fases 0–7 do `01-plano-implementacao.md` concluídas). Este documento descreve a arquitetura final, não mais um scaffold pendente.

**Estrutura de código (Core):**

| Área | Arquivos principais |
|---|---|
| Autenticação | `app/Livewire/Auth/*` (Login, ForgotPassword, ResetPassword, TwoFactorChallenge, VerifyEmailNotice), `app/Actions/Auth/*` |
| RBAC | `app/Models/Role.php`, `app/Models/Permission.php` (estendem os models do Spatie, com `Auditable`), `app/Http/Middleware/CheckPermission.php` |
| Menu / Sidebar | `app/Models/MenuSideBar.php`, `app/Livewire/Sidebar.php`, `app/Livewire/Admin/MenuSideBarManager.php` |
| Layout / UI | `resources/views/components/layouts/master.blade.php` (autenticado) e `guest.blade.php` (login/2FA/recuperação), componentes `x-button`/`x-card`/`x-modal`/`x-badge`/`x-table`/`x-alert` |
| App Config | `app/Models/AppConfig.php`, `app/Services/AppConfigService.php`, helpers `config_app()`/`config_app_media()`, `app/Livewire/Admin/AppConfigManager.php` |
| Notificações | `app/Models/PushSubscription.php`, `app/Notifications/*`, `app/Livewire/NotificationBell.php`, `app/Livewire/NotificationList.php` |
| Perfil do usuário | `app/Models/UserProfile.php`, `UserAddress.php`, `UserAdditionalData.php`, `app/Livewire/Settings/Profile.php` |
| Audit Logging | pacote `owen-it/laravel-auditing` (trait `Auditable` nos models), `app/Listeners/Audit/*` (eventos de login/logout), `app/Livewire/Admin/AuditManager.php` |
| Provider | `app/Providers/CoreServiceProvider.php` (Gate global de admin) |

> Este Laravel 13 não usa arquivos `EventServiceProvider`/`RouteServiceProvider` separados: rotas são registradas via `bootstrap/app.php` (`routes/web.php`, `routes/auth.php`, `routes/admin.php`, `routes/atendimento.php`) e listeners de evento são auto-descobertos pela convenção `app/Listeners/*`.

**Migrations do Core (`database/migrations/`):** cobrem users (+2FA, avatar, ativo/requires_2fa), permission_tables, menu_side_bars (+coluna `group`), audits, app_configs, notifications, push_subscriptions, user_profiles (+CPF/RG/telefone secundário), user_addresses, user_additional_data. Ver o diretório para a lista completa e datada — não é reproduzida aqui para evitar desatualização.

---

## O que Pertence ao Core

### Autenticação
- Login, logout, registro, recuperação de senha
- Email verification
- 2FA (two-factor authentication)
- Middleware de verificação de 2FA

### RBAC — Roles & Permissions (via Spatie Permission)
- Definição de roles (`admin`, `manager`, `operator`, etc.)
- Definição de permissions por área da aplicação
- Gates e Policies para verificação
- Seeder de roles e permissions iniciais

### Menu / Sidebar de Navegação
- Tabela `menu_side_bars` — itens de menu persistidos em banco (não hardcoded em Blade)
- Componente Livewire `Sidebar` que monta a árvore de menu a partir do banco
- Itens filtrados por permissão do usuário autenticado
- Suporte a grupos, sub-itens e ordenação (`order`) configurável via admin

### Layouts e Componentes UI Compartilhados
- `master.blade.php` — layout principal
- Componentes Blade: `<x-button>`, `<x-card>`, `<x-alert>`, `<x-modal>`
- Componente de notificações flash

### App Config
- Tabela `app_configs` — configurações da aplicação (chave/valor) editáveis sem precisar alterar `.env` ou fazer deploy
- Service `AppConfigService` (a implementar) com cache das configs
- Tela admin para editar configurações gerais (nome do sistema, logo, cores, flags de feature, etc.)

### Notificações
- Tabela `notifications` — notificações internas do usuário (padrão Laravel Notifications)
- Tabela `push_subscriptions` — inscrições de Web Push por dispositivo/navegador (com `content_encoding` para o payload)
- Componente Livewire de sino de notificações (contador de não lidas, marcar como lida)
- Canal de Web Push para notificações fora da aba ativa

### Perfil do Usuário
- Tabela `user_profiles` — dados de perfil (nome de exibição, bio, preferências, etc.)
- Tabela `user_additional_data` — dados adicionais/customizados do usuário
- Tabela `user_addresses` — endereços do usuário (pode ter múltiplos)
- Coluna `avatar_path` em `users` — foto/avatar do usuário
- Tela de "Meu Perfil" para o usuário editar seus próprios dados

### Audit Logging
- Tabela `audits` — registro de auditoria
- Model `AuditLog`
- Middleware que registra ações por usuário
- Interface de visualização de logs

---

## O que NÃO Pertence ao Core

| O que não vai | Onde vai |
|---|---|
| Models de domínio (Appointment, Payment) | Área respectiva da aplicação |
| Lógica de agendamento | Área respectiva da aplicação |
| Lógica de pagamento | Área respectiva da aplicação |
| Geração de relatórios | `app/Domain/Report/` (ou equivalente) |
| Qualquer `if ($user->hasRole('admin'))` relacionado a negócio | Área respectiva da aplicação |

---

## Namespace e Estrutura de Arquivos

```
app/
├── Http/
│   └── Controllers/
│       └── CoreController.php          ← App\Http\Controllers\CoreController
├── Livewire/
│   └── Dashboard.php                   ← App\Livewire\Dashboard
├── Models/                             ← (a implementar: AuditLog, AppConfig, MenuSideBar,
│                                           PushSubscription, UserProfile, UserAdditionalData,
│                                           UserAddress)
├── Services/                           ← (a implementar: AuthService, PermissionService,
│                                           AppConfigService)
└── Providers/
    ├── CoreServiceProvider.php
    ├── EventServiceProvider.php
    └── RouteServiceProvider.php

resources/
└── views/
    ├── components/
    │   └── layouts/
    │       └── master.blade.php        ← layout principal
    └── livewire/
        └── dashboard.blade.php

routes/
├── web.php
└── api.php

database/
└── migrations/
    ├── create_permission_tables.php            ← RBAC (Spatie)
    ├── create_menu_side_bars_table.php         ← Menu / Sidebar
    ├── create_audits_table.php                 ← Audit Logging
    ├── create_app_configs_table.php            ← App Config
    ├── create_notifications_table.php          ← Notificações
    ├── create_push_subscriptions_table.php     ← Notificações (Web Push)
    ├── create_user_profiles_table.php          ← Perfil do usuário
    ├── create_user_additional_data_table.php   ← Perfil do usuário
    └── create_user_addresses_table.php         ← Perfil do usuário
```

**Regra de namespace:** `App\` mapeia para `app/`, seguindo o padrão PSR-4 já registrado no `composer.json` do Laravel.

Exemplo completo:
- Namespace: `App\Livewire\Dashboard`
- Arquivo: `app/Livewire/Dashboard.php`

---

## Sistema de Layout

O layout principal fica em `resources/views/components/layouts/master.blade.php`.

Para usá-lo em qualquer view da aplicação:

```blade
{{-- Em qualquer view --}}
<x-layouts.master>
    <x-slot name="title">Agendamentos</x-slot>

    {{-- conteúdo da página --}}
</x-layouts.master>
```

Em um Livewire component, use o atributo `#[Layout]`:

```php
<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.master')]
class AppointmentList extends Component
{
    public function render()
    {
        return view('livewire.appointment-list');
    }
}
```

---

## Funcionalidades Implementadas

Todas as fases abaixo estão concluídas (ver `01-plano-implementacao.md` para o detalhamento de testes por fase).

### Autenticação e Segurança

- [x] Login com email + senha
- [x] Logout
- [x] Registro de usuário — **não implementado por decisão de produto**; admin cria usuários
- [x] Recuperação de senha (forgot/reset)
- [x] Email verification
- [x] Middleware `check2fa`
- [x] 2FA via TOTP (Google Authenticator)

### RBAC

- [x] Seeder de roles iniciais (`admin`, `manager`, `operator`)
- [x] Seeder de permissions por área da aplicação
- [x] Gate definitions em `CoreServiceProvider`
- [x] Blade directive `@can` funcionando com roles do Spatie

### Interface

- [x] Sidebar component (Livewire, itens dinâmicos por permissão, seções agrupadas, modo recolhido)
- [x] Dashboard com métricas reais do Core
- [x] Componentes Blade compartilhados: button, card, alert, badge, modal, table
- [x] Flash notifications (success, error, warning, info)
- [x] Interações leves (toggles, dropdowns, modais) via Alpine.js
- [x] Tema claro/escuro (paleta validada, persistido em `localStorage`)

### Menu (Sidebar dinâmica)

- [x] Migration `menu_side_bars` (+ coluna `group` para seções)
- [x] Model `MenuSideBar`
- [x] Componente Livewire `Sidebar` lendo os itens do banco
- [x] Filtro de itens por permissão do usuário
- [x] Tela admin de CRUD dos itens de menu (ordem, ícone, rota, grupo pai, seção) — valida rota e ícone antes de salvar

### App Config

- [x] Migration `app_configs`
- [x] Model `AppConfig`
- [x] Service `AppConfigService` com cache (valor + mídia)
- [x] Helpers `config_app()` / `config_app_media()`
- [x] Tela admin de edição das configurações
- [x] Seeder com chaves padrão, **efetivamente consumidas** pela aplicação (nome/logo/avatar padrão)

### Notificações

- [x] Migration `notifications`
- [x] Migration `push_subscriptions` (+ `content_encoding`)
- [x] Model `PushSubscription`
- [x] Classes de Notification do Laravel (canais: database, webhook, web push — não `mail`, ver `01-plano-implementacao.md`)
- [x] Componente Livewire de sino de notificações
- [x] Integração de Web Push no front-end (Service Worker + VAPID keys)

### Perfil do Usuário

- [x] Migration `user_profiles` (+ CPF/RG/telefone secundário)
- [x] Migration `user_additional_data`
- [x] Migration `user_addresses`
- [x] Coluna `avatar_path` em `users`
- [x] Models `UserProfile`, `UserAdditionalData`, `UserAddress`
- [x] Tela "Meu Perfil" (dados pessoais, endereços, avatar, segurança/2FA embutido)
- [x] Upload e recorte de avatar (cropper client-side em canvas, sem lib externa)

### Infraestrutura (Audit Logging)

- [x] Migration `audits`
- [x] Model de auditoria — via pacote `owen-it/laravel-auditing` (`OwenIt\Auditing\Models\Audit`), não um `AuditLog` próprio
- [x] Auditoria automática por model (trait `Auditable`) + Listeners para eventos de autenticação (`app/Listeners/Audit/*`) — **não há middleware genérico de auditoria de rota**
- [x] Interface admin para visualizar logs de auditoria

---

## Stack de Middleware Planejado

As rotas protegidas do sistema seguirão esta pilha:

```
web → auth → verified → check2fa → checkPermission('permission.name')
```

| Middleware | Responsabilidade |
|---|---|
| `auth` | Usuário autenticado (padrão Laravel) |
| `verified` | Email verificado |
| `check2fa` | 2FA completado na sessão |
| `checkPermission` | Permissão Spatie verificada |

---

## Registrando Componentes Livewire

No `CoreServiceProvider::boot()`:

```php
use Livewire\Livewire;
use App\Livewire\Dashboard;

public function boot(): void
{
    Livewire::component('dashboard', Dashboard::class);
    // Livewire::component('sidebar', Sidebar::class);
}
```

Como a aplicação não é modular, não há necessidade de prefixos de namespace (`core::`, `agendaai::` etc.) — os componentes Livewire e Blade são registrados e referenciados diretamente pelo nome, seguindo a convenção padrão do Laravel/Livewire.

---

## Stack de Referência

- **Laravel 13**
- **Livewire 4** — componentes reativos server-side
- **Alpine.js** — interatividade leve no front-end (toggles, dropdowns, transições)
- **Tailwind CSS** — estilização utility-first
