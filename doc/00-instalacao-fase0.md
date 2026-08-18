# 00 — Instalação: Fase 0 (Setup do Projeto)

Guia de instalação da base do projeto: Laravel 13 + Livewire 4 + Alpine.js + Tailwind CSS + Spatie Permission. Corresponde à **Fase 0** do `01-plano-implementacao.md`.

**Pré-requisito de ambiente:** PHP **8.3+** (o Laravel 13 exige `^8.3`). Se estiver no Windows, ver a seção de Troubleshooting no final antes de começar — é o problema mais comum nesta etapa.

---

## 1. Criar o projeto

Se o repositório `app-setup-core` já existir com as migrations prontas, clone em vez de criar do zero:

```bash
# Opção A — clonar o repositório existente (já tem migrations)
git clone <url-do-repo-app-setup-core> app
cd app
composer install

# Opção B — projeto novo do zero
composer create-project laravel/laravel app "13.*"
cd app
```

> Se a pasta `app` já existir com conteúdo de uma tentativa anterior, o `create-project` falha com `Project directory "..." is not empty`. Apague ou renomeie a pasta antes de rodar de novo:
> ```bash
> Remove-Item -Recurse -Force app   # PowerShell
> # ou: rm -rf app                  # bash/zsh
> ```

---

## 2. Configurar ambiente

```bash
cp .env.example .env
php artisan key:generate

# Editar .env: DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

---

## 3. Confirmar o Livewire 4

O Laravel 13 já vem com `livewire/livewire` declarado no `composer.json` do skeleton — normalmente **não é necessário rodar `composer require`** para instalá-lo.

```bash
composer show livewire/livewire
```

Se por algum motivo não estiver instalado, ou se a versão resolvida vier abaixo da `3.0.1` (a `3.0.0` exata tem CVEs conhecidas — o Composer bloqueia a instalação nesse caso), rode:

```bash
composer require livewire/livewire
```

> **Nota de versão:** o projeto usa **Livewire 4** (estável desde janeiro de 2026), no formato **class-based** (classe PHP + view Blade separadas) — não o Single-File Component, que é o novo padrão do `make:livewire` mas não o adotado aqui. Ao criar componentes, use a flag `--class`:
> ```bash
> php artisan make:livewire Dashboard --class
> ```
> Ver `03-ui-system.md` para as convenções completas (incluindo as diferenças de sintaxe entre v3 e v4).

Alpine.js já vem embutido no Livewire (injetado automaticamente via `@livewireScripts`) — não precisa instalar separado, a menos que seja usado fora do contexto Livewire.

---

## 4. Instalar Spatie Permission (RBAC)

```bash
composer require spatie/laravel-permission

php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

> Se o repositório já tem a migration `create_permission_tables.php` (caso da Opção A no passo 1), **não** rode o publish de novo — pule direto para o `migrate`.

---

## 5. Instalar Tailwind CSS v4 (via Vite)

```bash
npm install
npm install tailwindcss @tailwindcss/vite
npm install -D @tailwindcss/forms
```

Configurar `vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

Criar/editar `resources/css/app.css`:

```css
@import "tailwindcss";
@plugin "@tailwindcss/forms";

@theme {
    --font-sans: 'Inter', sans-serif;
}
```

Não crie `tailwind.config.js` — Tailwind v4 é CSS-first (ver `03-ui-system.md`).

---

## 6. Criar a estrutura de pastas do Core

```bash
mkdir -p app/Actions app/Services app/Repositories app/Livewire
mkdir -p resources/views/components/layouts
mkdir -p resources/views/livewire
```

---

## 7. Rodar as migrations

```bash
php artisan migrate
```

---

## 8. Subir o ambiente de desenvolvimento

```bash
# Terminal 1 — servidor Laravel
php artisan serve

# Terminal 2 — build de assets em watch mode
npm run dev
```

---

## Checklist rápido de verificação

```bash
php artisan --version              # confirma Laravel 13
php -v                              # confirma PHP 8.3+ (idealmente 8.4)
composer show livewire/livewire     # confirma Livewire 4.x
composer show spatie/laravel-permission
php artisan migrate:status          # confirma migrations aplicadas
```

Quando tudo isso estiver rodando (`php artisan serve` + `npm run dev` sem erro, migrations aplicadas), a Fase 0 está concluída e dá pra seguir para a **Fase 1 — Autenticação**.

---

## Troubleshooting

### `Cannot use laravel/laravel's latest version ... as it requires php ^8.3 which is not satisfied by your platform`

Sua versão de PHP está abaixo de 8.3. No Windows, o jeito mais rápido de resolver é instalar o **Laravel Herd** (https://herd.laravel.com/windows), que já vem com PHP 8.3/8.4 prontos.

Depois de instalar:

1. Complete o wizard do Herd até o fim (pode clicar em "Skip for now" na tela de licença Pro)
2. **O instalador não adiciona o PHP ao PATH automaticamente em todos os casos.** Confirme:
   ```powershell
   where.exe php
   php -v
   ```
3. Se aparecer só uma versão antiga (ex.: do XAMPP), localize onde o Herd instalou o PHP:
   ```powershell
   Get-ChildItem -Path "$env:USERPROFILE\.config\herd" -Recurse -Filter "php.exe" -ErrorAction SilentlyContinue
   ```
4. Adicione a pasta encontrada (ex.: `C:\Users\<usuario>\.config\herd\bin\php84`) manualmente ao PATH:
   - Menu Iniciar → "variáveis de ambiente" → "Editar as variáveis de ambiente do sistema" → "Variáveis de Ambiente..."
   - Em **Variáveis do sistema**, selecione `Path` → **Editar** → **Novo** → cole o caminho da pasta (sem o `php.exe`)
   - Selecione a linha e clique **"Mover para Cima"** até ficar **acima** de qualquer entrada de PHP antigo (ex.: `C:\xampp\php`)
   - Clique **OK** em todas as janelas — confirme que salvou de fato, reabrindo as variáveis de ambiente e checando se a entrada persistiu
5. Abra um terminal **novo** (fechar e reabrir o VS Code inteiro, não só a aba do terminal — o terminal integrado cacheia o PATH da sessão em que foi aberto) e confirme:
   ```powershell
   where.exe php
   php -v
   ```

### `Could not fetch https://api.github.com/repos/... please review your configured GitHub OAuth`

O Composer bateu no limite de requisições não-autenticadas da API do GitHub. Gere um token:

1. Acesse: https://github.com/settings/tokens/new
2. Não precisa marcar nenhum escopo — role até o fim e gere
3. Cole o token quando o terminal pedir `Token (hidden):`, ou configure direto:
   ```bash
   composer config -g github-oauth.github.com SEU_TOKEN_AQUI
   ```

O token fica salvo em `auth.json` global do Composer — não pede de novo depois disso.

### `Project directory "..." is not empty`

Sobra de uma tentativa anterior de `create-project`. Apague ou renomeie a pasta antes de rodar de novo (ver nota no passo 1).

### Composer resolve `livewire/livewire` para uma versão diferente da esperada (ex.: trava em `3.0.0` exata ou sobe direto para `4.x` quando você queria `3.x`)

- Para travar numa major específica: `composer require livewire/livewire:^3.6` (ou a major desejada)
- Se o `composer.json` já tem uma constraint problemática gravada, edite a linha diretamente no arquivo e rode `composer update livewire/livewire` em vez de `require`
- Sempre confirme o resultado real com `composer show livewire/livewire` — não assuma pela constraint do `composer.json`, confirme a versão resolvida
