---
name: senior-architect
description: Skill de arquitetura sênior especializada em PHP, Laravel, Livewire, Tailwind CSS e MySQL. Inclui padrões de design, decisões de stack, análise de dependências, estrutura de aplicação única (não modular) e boas práticas para sistemas web enterprise. Use ao projetar arquitetura, tomar decisões técnicas, avaliar trade-offs, definir padrões de integração ou revisar estrutura da aplicação.
---

# Senior Architect — PHP / Laravel / Livewire / Tailwind / MySQL

Toolkit completo para arquiteto sênior com foco no ecossistema Laravel moderno.

---

## Stack de Referência

| Camada       | Tecnologia                                      |
|--------------|--------------------------------------------------|
| Linguagem    | PHP 8.3+                                        |
| Framework    | Laravel 13                                      |
| Frontend     | Livewire 4 + Alpine.js + Tailwind CSS           |
| Banco        | MySQL 8+ (via Eloquent ORM)                     |
| Auth         | Laravel Sanctum + Spatie Permission + 2FA       |
| Testes       | PHPUnit + Pest PHP                              |
| Build        | Vite (Laravel Plugin)                           |
| DevOps       | Docker / Laravel Sail / GitHub Actions          |
| Qualidade    | Laravel Pint (PSR-12) + Larastan (PHPStan)      |

---

## Capacidades Principais

### 1. Design de Arquitetura da Aplicação

**Princípios:**
- A aplicação é única (não modular) — todo o código vive em `app/`, seguindo a estrutura padrão do Laravel
- Domínios de negócio são organizados por convenção de nomenclatura e, quando necessário, por subpastas dentro das mesmas camadas (ex.: `app/Actions/Agendamentos/`, `app/Actions/Pagamentos/`) — não por diretórios isolados tipo módulo
- Camadas não se acoplam diretamente de forma desnecessária — comunicam via Events, Jobs ou Service classes quando cruzam domínios
- O **Core** é a parte transversal da aplicação: Auth, RBAC, Auditoria, Notificações, App Config, Menu — está espalhado nas mesmas pastas de `app/`, mas logicamente é o que todo o resto depende

**Estrutura padrão da aplicação:**
```
app/
├── Actions/            # Casos de uso (Single Responsibility)
├── Console/
│   └── Commands/
├── Events/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/       # Form Requests
├── Jobs/
├── Listeners/
├── Livewire/           # Componentes Livewire
├── Models/
├── Notifications/
├── Policies/
├── Providers/
├── Repositories/
└── Services/

resources/
├── css/
├── js/
└── views/
    ├── components/     # Blade Components
    └── livewire/       # Views dos componentes Livewire

routes/
├── web.php
├── api.php
└── console.php

database/
├── migrations/
├── seeders/
└── factories/

tests/
├── Unit/
└── Feature/
```

Quando um domínio cresce muito (muitos Models, Actions, Requests), agrupe por subpasta dentro de cada camada em vez de criar uma estrutura paralela tipo módulo — por exemplo `app/Models/Agendamentos/Appointment.php`, mantendo o namespace `App\Models\Agendamentos\Appointment`.

---

### 2. Padrões de Design Recomendados

#### Repository Pattern
```php
// app/Repositories/NomeRepositoryInterface.php
interface NomeRepositoryInterface {
    public function all(): Collection;
    public function find(string $id): Model;
    public function create(array $data): Model;
    public function update(string $id, array $data): Model;
    public function delete(string $id): bool;
}
```

#### Service Layer
```php
// app/Services/NomeService.php
class NomeService {
    public function __construct(
        private NomeRepositoryInterface $repo
    ) {}

    public function processar(array $dados): Model {
        // lógica de negócio aqui
        return $this->repo->create($dados);
    }
}
```

#### Action Classes (Single Responsibility)
```php
// app/Actions/CriarNomeAction.php
class CriarNomeAction {
    public function execute(NomeData $data): Model {
        // uma única responsabilidade
    }
}
```

#### Form Requests para validação
```php
// app/Http/Requests/StoreNomeRequest.php
class StoreNomeRequest extends FormRequest {
    public function rules(): array {
        return [
            'campo' => ['required', 'string', 'max:255'],
        ];
    }
}
```

---

### 3. Componentes Livewire

**Convenções:**
- Componentes em `app/Livewire/`
- Views em `resources/views/livewire/`
- Usar `#[Validate]` attributes do Livewire 4 para validação inline
- Preferir `wire:model.live` para feedback imediato; `wire:model.blur` para campos pesados

**Exemplo de componente:**
```php
// app/Livewire/AgendamentoForm.php
namespace App\Livewire;

use Livewire\Attributes\Validate;
use Livewire\Component;

class AgendamentoForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $titulo = '';

    #[Validate('required|date')]
    public string $data = '';

    public function salvar(): void
    {
        $this->validate();
        // persistir...
        $this->dispatch('agendamento-criado');
        $this->reset();
    }

    public function render()
    {
        return view('livewire.agendamento-form');
    }
}
```

O Livewire descobre o componente automaticamente pela convenção `App\Livewire\NomeDoComponente` → `<livewire:nome-do-componente />`. Registro manual só é necessário para aliases customizados, feito no `boot()` de um Service Provider:

```php
use Livewire\Livewire;

public function boot(): void
{
    Livewire::component('agendamento-form', \App\Livewire\AgendamentoForm::class);
}
```

---

### 4. Tailwind CSS com Blade

**Estrutura de layout base:**
```html
{{-- resources/views/components/layouts/master.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 text-gray-900 antialiased">
    <livewire:sidebar />
    <main class="ml-64 p-6">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
```

**Tailwind v4 é CSS-first** — não há `tailwind.config.js`. O tema e os plugins são declarados direto em `resources/css/app.css`:

```css
@import "tailwindcss";
@plugin "@tailwindcss/forms";

@theme {
    --color-primary: oklch(55% 0.2 260);
    --font-sans: 'Inter', sans-serif;
}
```

O content scanning é automático — o Tailwind detecta as classes usadas em todo o projeto sem precisar de um array `content` configurado.

---

### 5. MySQL — Boas Práticas com Eloquent

**Migrations:**
```php
Schema::create('nome_tabela', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('titulo');
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->softDeletes();
    // índices explícitos para queries frequentes
    $table->index(['user_id', 'created_at']);
});
```

**Evitar N+1:**
```php
// Ruim
$items = Item::all();
foreach ($items as $item) { $item->user->name; }

// Bom
$items = Item::with('user')->get();

// Melhor (quando escopo é grande)
$items = Item::with('user:id,name')->paginate(20);
```

**Scopes para queries reutilizáveis:**
```php
// No model
public function scopeAtivo(Builder $query): void
{
    $query->where('ativo', true);
}

// Uso
Item::ativo()->with('user')->paginate();
```

---

### 6. RBAC e Permissões (Spatie)

**Padrão de nomenclatura:** `[acao]-[recurso]`
```
create-agendamentos
edit-agendamentos
delete-agendamentos
view-relatorios
```

**Em controllers:**
```php
$this->authorize('create-agendamentos');
```

**Em views Blade:**
```html
@can('edit-agendamentos')
    <button wire:click="editar">Editar</button>
@endcan
```

**Após alterar permissões:**
```bash
php artisan permission:cache-reset
```

---

### 7. Funcionalidades Transversais do Core

Além de Auth e RBAC, o Core da aplicação cobre:

- **Menu / Sidebar dinâmica** — itens de navegação persistidos em banco (`menu_side_bars`), filtrados por permissão do usuário
- **App Config** — configurações da aplicação editáveis via admin (`app_configs`), com cache
- **Notificações** — canal in-app (`notifications`) e Web Push (`push_subscriptions`), via Laravel Notifications
- **Perfil do Usuário** — dados de perfil, endereços e dados adicionais (`user_profiles`, `user_addresses`, `user_additional_data`), além de avatar
- **Audit Logging** — registro de ações do usuário (`audits`)

Essas features vivem nas mesmas pastas de `app/` (Models, Services, Livewire) e devem ser tratadas como infraestrutura compartilhada — qualquer domínio de negócio pode depender delas, mas elas não devem depender de nenhum domínio específico.

---

### 8. Análise de Dependências

**Verificar saúde do projeto:**
```bash
# Dependências desatualizadas
composer outdated

# Vulnerabilidades de segurança
composer audit

# Análise estática
./vendor/bin/phpstan analyse --level=5

# Code style
./vendor/bin/pint --test
```

---

### 9. Decisões de Arquitetura — Framework

| Cenário | Decisão |
|---------|---------|
| UI com estado (formulários, listas reativas) | Livewire Component |
| UI sem estado (display only) | Blade Component (`<x-...>`) |
| Operação pesada (relatório, email) | Job + Queue |
| Notificação ao usuário | Laravel Notification (DB + Push) |
| Comunicação entre domínios distintos | Laravel Events + Listeners |
| Lógica reutilizável entre domínios | Trait ou Service no Core |
| Consulta complexa | Eloquent Query Builder + Scope |
| Consulta muito pesada | Raw Query ou View materializada no MySQL |
| Validação de entrada | Form Request |
| Transformação de saída | API Resource (`JsonResource`) |

---

## Fluxo de Desenvolvimento

### 1. Novo recurso

```bash
# 1. Criar migration
php artisan make:migration create_x_table

# 2. Criar model
php artisan make:model X

# 3. Criar controller
php artisan make:controller XController

# 4. Criar componente Livewire
php artisan make:livewire XForm

# 5. Registrar rota em routes/web.php

# 6. Rodar migration
php artisan migrate

# 7. Testes
php artisan test --filter XTest
```

### 2. Cache e otimização
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 3. Reset completo (dev)
```bash
php artisan migrate:fresh --seed
php artisan config:clear && php artisan cache:clear
php artisan view:clear && php artisan route:clear
```

---

## Pontos Críticos de Arquitetura

1. **Middleware order** obrigatório: `auth → verified → check2fa` (ver `02-core.md`)
2. **UUID em vez de auto-increment** no model `User` — `$user->id` é string UUID
3. **Service Providers** ficam em `app/Providers/` e são registrados em `bootstrap/providers.php` (Laravel 13) — não duplicar registros
4. **Livewire** — componentes seguem a convenção de namespace `App\Livewire\*`; alias manual só quando necessário
5. **Um único `vite.config.js`** na raiz do projeto — não fragmentar o build por domínio
6. **Permissões são cacheadas** — sempre rodar `permission:cache-reset` após seeders de permissão
7. **Eventos entre domínios** — usar `Event::dispatch()` e Listeners registrados no `EventServiceProvider`
8. **Soft Deletes** — preferir `softDeletes()` em tabelas de domínio para manter histórico
9. **Core é transversal** — Menu, App Config, Notificações, Perfil e Auditoria não devem conter regra de negócio de domínio algum

---

## Qualidade e Testes

```bash
# Rodar todos os testes
php artisan test

# Testes de uma feature específica
php artisan test --filter AgendaAiTest

# Cobertura
php artisan test --coverage

# PHPStan nível 5
./vendor/bin/phpstan analyse app/ --level=5

# Formatação automática
./vendor/bin/pint
```

**Estrutura de teste:**
```
tests/
├── Unit/
│   └── NomeServiceTest.php
└── Feature/
    └── NomeControllerTest.php
```

---

## Referências Internas

- Contexto completo do projeto: `.claude/commands/projeto.md`
- Estrutura da aplicação: `/app/`
- Migrations: `/database/migrations/`
