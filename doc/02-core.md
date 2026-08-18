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

## Estado Atual (Scaffold)

A aplicação já conta com o scaffold de código do Core (controllers, providers, Livewire) e, no banco de dados, já existem as migrations de boa parte das funcionalidades planejadas — a implementação da lógica (Models, Services, Livewire components, views) ainda está pendente para várias delas.

**Código implementado:**

| Arquivo | Namespace | Responsabilidade |
|---|---|---|
| `app/Livewire/Dashboard.php` | `App\Livewire\Dashboard` | Componente Livewire da dashboard |
| `resources/views/components/layouts/master.blade.php` | — | Layout principal da aplicação |
| `app/Http/Controllers/CoreController.php` | `App\Http\Controllers\CoreController` | Controller base do Core |
| `app/Providers/CoreServiceProvider.php` | `App\Providers\CoreServiceProvider` | Provider principal do Core |
| `app/Providers/EventServiceProvider.php` | `App\Providers\EventServiceProvider` | Registro de listeners do Core |
| `app/Providers/RouteServiceProvider.php` | `App\Providers\RouteServiceProvider` | Carrega rotas da aplicação |

**Migrations já existentes (`database/migrations/`):**

| Migration | Funcionalidade |
|---|---|
| `0001_01_01_000000_create_users_table.php` | Usuários (base do Laravel) |
| `0001_01_01_000001_create_cache_table.php` | Cache (base do Laravel) |
| `0001_01_01_000002_create_jobs_table.php` | Filas/Jobs (base do Laravel) |
| `2025_05_28_145941_create_permission_tables.php` | RBAC — tabelas do Spatie Permission |
| `2025_05_30_032716_add_2fa_and_verified_to_users_table.php` | Autenticação — colunas de 2FA e verificação de email |
| `2025_06_01_190341_add_confirmed_2fa_to_users_table.php` | Autenticação — confirmação do 2FA |
| `2025_06_03_015601_create_menu_side_bars_table.php` | Menu — itens da sidebar armazenados em banco |
| `2025_06_06_151109_create_audits_table.php` | Audit Logging — registro de auditoria |
| `2025_06_06_201335_create_app_configs_table.php` | App Config — configurações da aplicação |
| `2025_06_07_155128_create_notifications_table.php` | Notificações |
| `2025_06_08_000000_create_push_subscriptions_table.php` | Notificações — inscrições de Web Push |
| `2025_06_09_000000_add_avatar_path_to_users_table.php` | Perfil — avatar do usuário |
| `2025_06_09_000000_add_content_encoding_to_push_subscriptions_table.php` | Notificações — encoding do payload de push |
| `2025_06_11_000000_create_user_additional_data_table.php` | Perfil — dados adicionais do usuário |
| `2025_06_11_000001_create_user_addresses_table.php` | Perfil — endereços do usuário |
| `2025_09_20_172535_create_user_profiles_table.php` | Perfil — dados de perfil do usuário |

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

## Funcionalidades Planejadas

### Autenticação e Segurança

- [ ] Login com email + senha
- [ ] Logout
- [ ] Registro de usuário (se aplicável ao produto)
- [ ] Recuperação de senha (forgot/reset)
- [ ] Email verification
- [ ] Middleware `check2fa`
- [ ] 2FA via TOTP (Google Authenticator)

### RBAC

- [ ] Seeder de roles iniciais (`admin`, `manager`, `operator`)
- [ ] Seeder de permissions por área da aplicação
- [ ] Gate definitions em `CoreServiceProvider`
- [ ] Blade directive `@can` funcionando com roles do Spatie

### Interface

- [ ] Sidebar component (Livewire, itens dinâmicos por permissão)
- [ ] Dashboard com métricas reais (widgets por área)
- [ ] Componentes Blade compartilhados: button, card, alert, badge, modal, table
- [ ] Flash notifications (success, error, warning, info)
- [ ] Interações leves (toggles, dropdowns, modais) via Alpine.js

### Menu (Sidebar dinâmica)

- [x] Migration `menu_side_bars`
- [ ] Model `MenuSideBar`
- [ ] Componente Livewire `Sidebar` lendo os itens do banco
- [ ] Filtro de itens por permissão do usuário
- [ ] Tela admin de CRUD dos itens de menu (ordem, ícone, rota, grupo pai)

### App Config

- [x] Migration `app_configs`
- [ ] Model `AppConfig`
- [ ] Service `AppConfigService` com cache
- [ ] Helper/facade para leitura rápida de config (ex.: `config_app('nome_do_sistema')`)
- [ ] Tela admin de edição das configurações

### Notificações

- [x] Migration `notifications`
- [x] Migration `push_subscriptions` (+ `content_encoding`)
- [ ] Model `PushSubscription`
- [ ] Classes de Notification do Laravel (canais: database, mail, web push)
- [ ] Componente Livewire de sino de notificações
- [ ] Integração de Web Push no front-end (Service Worker + VAPID keys)

### Perfil do Usuário

- [x] Migration `user_profiles`
- [x] Migration `user_additional_data`
- [x] Migration `user_addresses`
- [x] Coluna `avatar_path` em `users`
- [ ] Models `UserProfile`, `UserAdditionalData`, `UserAddress`
- [ ] Tela "Meu Perfil" (dados pessoais, endereços, avatar)
- [ ] Upload e recorte de avatar

### Infraestrutura

- [x] Migration `audits`
- [ ] Model `AuditLog`
- [ ] Middleware de auditoria (registra route, user_id, payload, IP)
- [ ] Interface admin para visualizar logs de auditoria

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
