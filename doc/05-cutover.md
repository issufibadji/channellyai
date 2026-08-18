# 05 — Checklist de Go-Live

Este documento cobre a validação final antes de colocar o sistema em produção. Esta é uma reconstrução greenfield — não há sistema legado em execução paralela. O foco é garantir que o produto novo está completo, estável e seguro antes do lançamento.

---

## Infraestrutura Core

Todas as funcionalidades de infraestrutura devem estar implementadas e testadas antes do go-live:

- [ ] Fluxo de autenticação completo: login, logout e recuperação de senha
- [ ] 2FA implementado e middleware `check2fa` ativo nas rotas protegidas
- [ ] Email verification configurado e funcionando
- [ ] RBAC: roles e permissions definidos e seeder executado
- [ ] Sidebar exibe itens corretos por role/permission (menu dinâmico via `menu_side_bars`)
- [ ] App Config funcionando (configurações lidas de `app_configs`, com cache)
- [ ] Notificações internas e Web Push funcionando (`notifications` e `push_subscriptions`)
- [ ] Perfil do usuário completo (dados, endereços, avatar)
- [ ] Audit logging ativo (middleware registrando ações dos usuários)
- [ ] Flash notifications funcionando para success, error, warning e info

---

## Funcionalidades de Negócio

Esta seção deve ser preenchida com o checklist específico de cada funcionalidade de negócio da aplicação. Use os itens abaixo como modelo genérico para cada feature/domínio antes do go-live:

### Feature X

- [ ] CRUD completo (criar, listar, editar, excluir)
- [ ] Fluxo principal testado end-to-end
- [ ] Filtros e paginação na listagem funcionando
- [ ] Validações de negócio cobertas (regras específicas do domínio)
- [ ] Notificação (email, in-app ou push) disparada nos eventos relevantes

### Integrações externas (se houver)

- [ ] Credenciais/chaves de API em `.env`, nunca no código
- [ ] Fluxo testado em sandbox/homologação
- [ ] Webhooks recebidos e processados corretamente
- [ ] Tratamento de falha e retry implementado

### Relatórios (se houver)

- [ ] Relatórios geram com dados reais do banco
- [ ] Filtros de data e período funcionando
- [ ] Export (PDF ou CSV) testado com volume real de dados
- [ ] Performance de queries validada (sem timeout em períodos longos)

---

## Qualidade de Código

- [ ] Todos os testes feature passando: `php artisan test`
- [ ] Zero erros de Pint: `./vendor/bin/pint --test`
- [ ] Nenhum `dd()`, `dump()` ou `var_dump()` no código
- [ ] Nenhuma credencial ou secret hardcoded no código
- [ ] Revisão de N+1 queries com Laravel Debugbar desativado em produção

---

## Performance

- [ ] `php artisan optimize` executado
- [ ] Queue worker configurado e testado sob carga
- [ ] Indexes de banco revisados para queries frequentes
- [ ] Assets buildados para produção: `npm run build`
- [ ] Imagens otimizadas (sem assets desnecessariamente pesados)
- [ ] Eager loading aplicado nas listagens (`with()`)

---

## Segurança

- [ ] `.env` não está no repositório (verificar `.gitignore`)
- [ ] `APP_DEBUG=false` configurado em produção
- [ ] `APP_ENV=production` configurado
- [ ] HTTPS enforced (certificado SSL válido)
- [ ] CSRF protection ativa (padrão Laravel — não remover `@csrf`)
- [ ] Rate limiting configurado nas rotas de autenticação
- [ ] Tokens e chaves de API rotacionados para produção (não usar keys de sandbox/dev)
- [ ] VAPID keys de produção configuradas para Web Push
- [ ] Headers de segurança configurados (HSTS, X-Content-Type-Options, etc.)

---

## Deployment

### Passos de deploy

```bash
# 1. Instalar dependências sem pacotes de dev
composer install --no-dev --optimize-autoloader

# 2. Buildar assets para produção
npm ci
npm run build

# 3. Configurar variáveis de ambiente de produção
cp .env.production .env
php artisan key:generate --force  # apenas se necessário

# 4. Executar migrations
php artisan migrate --force

# 5. Cachear configurações, rotas, views e eventos
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Symlink de storage
php artisan storage:link
```

### Checklist de deployment

- [ ] `.env.production` configurado com todas as variáveis (DB, MAIL, QUEUE, APP_KEY, VAPID, etc.)
- [ ] `composer install --no-dev --optimize-autoloader` executado
- [ ] `npm run build` executado e assets no `public/build/`
- [ ] Migrations executadas: `php artisan migrate --force`
- [ ] Seeders de RBAC (roles/permissions) e menu executados
- [ ] Todos os caches gerados (config, route, view, event)
- [ ] Storage symlink criado: `php artisan storage:link`
- [ ] Backup do banco de dados realizado antes do deploy
- [ ] Queue worker (Supervisor ou similar) configurado e rodando
- [ ] Cron do Laravel Scheduler configurado: `* * * * * php /path/to/artisan schedule:run`

---

## Configuração de Queue Worker (Supervisor)

Exemplo de configuração Supervisor para o worker de filas:

```ini
[program:app-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/app/storage/logs/worker.log
stopwaitsecs=3600
```

---

## Validação Final (Smoke Test)

Execute manualmente antes de liberar acesso aos usuários:

- [ ] Login com usuário admin funciona
- [ ] Sidebar exibe todos os menus esperados para o role admin
- [ ] Dashboard carrega sem erros
- [ ] Editar configurações em App Config e ver refletido na aplicação
- [ ] Editar "Meu Perfil" (dados, endereço, avatar) funciona
- [ ] Receber uma notificação de teste (in-app e/ou push)
- [ ] Fluxo principal de negócio testado de ponta a ponta
- [ ] Logout funciona
- [ ] Tentativa de acessar rota protegida sem login redireciona para login
- [ ] Tentativa de acessar rota sem permissão retorna 403

---

## Rollback de Emergência

Se um problema crítico for detectado em produção:

```bash
# Reverter último deploy (se usando zero-downtime)
# 1. Reativar versão anterior via servidor web

# Reverter última migration (se necessário)
php artisan migrate:rollback

# Limpar caches corrompidos
php artisan optimize:clear

# Reiniciar workers
php artisan queue:restart
```

Mantenha sempre o backup do banco realizado **antes** do deploy disponível para restauração.
