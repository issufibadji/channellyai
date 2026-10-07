# 06 — Relatório de Auditoria (somente leitura)

> Auditoria realizada no repositório `channellyai`, branch `master` (a branch `schoolteacher` pedida pelo orquestrador não existe no repositório local no momento da auditoria — `git branch --show-current` retornou `master`; único branch encontrado). Nenhum arquivo do projeto foi alterado; este documento é o único artefato criado.

---

## Resumo executivo

O ChannellyAI hoje é uma aplicação Laravel 13 + Livewire 4 single-tenant: não existe modelo de "estabelecimento"/tenant, nem isolamento de dados por cliente — todas as tabelas de negócio (`clientes`, `canais`, `atendimentos`, `chatbot_regras`) são globais, sem `tenant_id` ou equivalente. O módulo "Core" (auth, 2FA, RBAC via Spatie, menu dinâmico, App Config, notificações in-app/web-push/webhook, auditoria via owen-it/laravel-auditing, perfil de usuário) está implementado e coberto por testes de feature. O módulo de domínio "Atendimento com IA" também existe e é funcional como CRUD interno (Dashboard, Atendimentos, Clientes, Canais, IA e Chatbot, Relatórios), mas não possui nenhuma integração real com canais externos (WhatsApp/Instagram/Facebook/E-mail) nem com o motor de IA externo "NanoClaw" — o `ChatbotEngine` é um motor de palavra-chave local, documentado pelos próprios autores como stub à espera da integração. Não existe módulo acadêmico (Curso/Turma/Módulo/Conteúdo) no código — essa hipótese do orquestrador não se confirma; os nomes de arquivo em `doc/` (`02-core.md`, `03-ui-system.md`, `04-atendimento-ia.md`) já correspondem ao domínio de atendimento, não a um domínio escolar. Não há rotas de API, Sanctum não está instalado apesar de citado em `doc/senior-architect.md`. Não há filas/jobs assíncronos em uso (`ShouldQueue` não aparece em lugar nenhum). O ambiente de dev usa SQLite (`database/database.sqlite`), produção é via Docker (Nginx + PHP-FPM, MySQL). A documentação em `doc/` está, de modo geral, atualizada e alinhada ao código, com pendências explicitamente marcadas como tal.

---

## 1. Visão geral

**Stack (via `composer.json` / `package.json`):**

| Dependência | Versão | Função |
|---|---|---|
| `laravel/framework` | ^13.17 | Framework base |
| `livewire/livewire` | ^4.4 | Componentes reativos server-driven |
| `spatie/laravel-permission` | ^8.3 | RBAC (roles/permissions), `Role`/`Permission` estendidos em `app/Models` |
| `owen-it/laravel-auditing` | ^14.0 | Trilha de auditoria automática por model |
| `pragmarx/google2fa` | ^9.1 | TOTP para 2FA |
| `bacon/bacon-qr-code` | ^3.1 | Geração de QR code do 2FA |
| `minishlink/web-push` | ^11.0 | Envio de notificações Web Push |
| `blade-ui-kit/blade-heroicons` | ^2.7 | Ícones no menu/UI |
| `fakerphp/faker` | ^1.24 (produção) | Dados fake — movido para produção por necessidade do seeder (`83d6929`) |
| `barryvdh/laravel-debugbar`, `laravel/pail`, `laravel/pao`, `pint`, `mockery`, `collision`, `phpunit/phpunit` | dev | Qualidade/debug, somente dev |
| `tailwindcss` v4, `@tailwindcss/vite`, `vite` 8, `laravel-vite-plugin` | front-end | Build de assets |
| `@laravel/multiplex` (optional) | front-end | Opcional, não confirmado em uso |

Não encontrados no `composer.json`: `laravel/sanctum`, `pestphp/pest` (apesar de citados em `doc/senior-architect.md` como stack de referência — ver seção 14), nenhum SDK de WhatsApp/Instagram/Facebook/IA.

PHP exigido: `^8.3` (composer.json); Dockerfile de produção usa `php:8.4-fpm-alpine` — discrepância de versão entre o mínimo declarado e a imagem de produção (não verificado se é intencional).

---

## 2. Estrutura do projeto

| Pasta | Responsabilidade (uma linha) |
|---|---|
| `app/Livewire/Auth` | Telas de login, recuperação de senha, verificação de e-mail, desafio 2FA |
| `app/Livewire/Admin` | CRUD administrativo: Usuários, Menu, Papéis, Permissões, Vínculo Papéis/Usuários, Config, Auditorias, Anúncios |
| `app/Livewire/Atendimento` | Painel de domínio: Dashboard, Atendimentos (lista/detalhe), Clientes, Canais, Chatbot, Relatórios |
| `app/Livewire/Settings` | Perfil do usuário e configuração de 2FA |
| `app/Models` | Models transversais (`User`, `Role`, `Permission`, `AppConfig`, `MenuSideBar`, `PushSubscription`, `SystemAnnouncement`, `UserProfile`/`UserAddress`/`UserAdditionalData`) |
| `app/Models/Atendimento` | Models de domínio: `Cliente`, `Canal`, `Atendimento`, `AtendimentoMensagem`, `ChatbotRegra` |
| `app/Services/Atendimento` | `ChatbotEngine` (motor de regras) e `ChatbotResponse` (DTO de saída) |
| `app/Actions/Auth` | Ações de login/2FA (tentativa de login, confirmar/ativar/desativar 2FA, logout, códigos de recuperação) |
| `app/Listeners/Audit` | Listeners de eventos de autenticação para registrar auditoria (login ok/falho, logout) |
| `app/Notifications` + `app/Notifications/Channels` + `.../Messages` | Notificação de anúncio e de criação de conta; canais customizados `webhook` e `web-push` |
| `app/Http/Middleware` | `CheckPermission` (checagem de permissão Spatie) e `CheckTwoFactor` (gate de 2FA pós-login) |
| `app/Repositories` | Pasta existe mas não foi inspecionada em detalhe (ver Lacunas) |
| `app/Concerns` | Traits reutilizáveis (`HasPushSubscriptions` usado em `User`) |
| `routes/{web,auth,admin,atendimento}.php` | Divisão de rotas por área, carregadas via `bootstrap/app.php` |
| `database/migrations` | 23 migrations, cobrindo Core e domínio de Atendimento |
| `database/seeders` | `DatabaseSeeder`, `RolePermissionSeeder`, `MenuSideBarSeeder`, `AppConfigSeeder` |
| `tests/Feature` e `tests/Unit` | Testes de feature cobrindo Auth, RBAC, Admin, Atendimento, Audit, Notifications (ver Seção 11) |
| `doc/` | 6 documentos de fase + `senior-architect.md` (skill/diretriz de arquitetura) + imagens de referência (`banner.png`, `banner1.png`, `dashboard.png`) |

Não existe nenhuma pasta/arquivo de domínio acadêmico (`Curso`, `Turma`, `Modulo`, `Conteudo`, `AlunoProgresso`) em `app/Models`, `app/Livewire`, `database/migrations` ou `routes/` — busca por essas classes não retornou nenhuma ocorrência.

---

## 3. Multi-tenancy

**Não implementado.** Evidências:

- Nenhuma migration cria uma tabela `estabelecimentos`/`tenants`/`empresas` (lista completa das 23 migrations verificada — ver Seção 4).
- Nenhuma coluna `tenant_id`/`estabelecimento_id`/`empresa_id` em qualquer migration de `clientes`, `canais`, `atendimentos`, `atendimento_mensagens`, `chatbot_regras`, `users`.
- Nenhum global scope, middleware de resolução de tenant, ou trait de "BelongsToTenant" encontrado em `app/Models`, `app/Http/Middleware` ou `app/Providers`.
- `CoreServiceProvider::boot()` define apenas um `Gate::before` global de bypass para `admin`; não há nenhuma lógica de resolução de contexto de estabelecimento.
- `app/Services/Atendimento/CanalManager` e demais Livewire do domínio consultam os models diretamente (`Canal::orderBy('nome')->get()`) sem qualquer filtro de tenant.

Conclusão: o sistema hoje gerencia um único "estabelecimento" implícito (a instância inteira da aplicação); o cadastro de múltiplos estabelecimentos descrito no objetivo futuro do usuário não existe no código atual, nem como estrutura preparatória (sem coluna "nullable" de tenant, sem feature flag).

---

## 4. Modelo de dados

Migrations relevantes (`database/migrations/`), em ordem:

| Migration | Tabela | Observação |
|---|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` | Base padrão do Laravel |
| `0001_01_01_000001/2` | `cache`, `jobs` | Infra padrão Laravel (fila/cache em DB) |
| `2026_08_18_153508_create_permission_tables` | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie Permission padrão |
| `2026_08_18_162859_add_two_factor_columns_to_users_table` | `users` | Colunas TOTP (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`) |
| `2026_08_18_170105_create_menu_side_bars_table` + `...062345_add_group_to_menu_side_bars_table` | `menu_side_bars` | Menu dinâmico (parent_id, label, group, icon, route_name, permission, order) |
| `2026_08_18_180331_add_active_and_requires_2fa_to_users_table` | `users` | Flags `active`, `requires_2fa` |
| `2026_08_18_180332_add_tag_to_permissions_table` | `permissions` | Campo `tag` adicional |
| `2026_08_18_191043_create_app_configs_table` | `app_configs` | Config key/value/media/required |
| `2026_08_18_192205_create_audits_table` | `audits` | Tabela do pacote owen-it/laravel-auditing |
| `2026_08_18_201327_create_notifications_table` | `notifications` | Notificações padrão Laravel (polimórfica) |
| `2026_08_18_212850/2_create_push_subscriptions/system_announcements_table` | `push_subscriptions`, `system_announcements` | Web Push + Anúncios |
| `2026_08_19_024929..024932` + `030428` | `users.avatar_path`, `user_profiles`, `user_addresses`, `user_additional_data` | Perfil estendido do usuário |
| `2026_08_19_054848..054852` | `clientes`, `canais`, `atendimentos`, `atendimento_mensagens`, `chatbot_regras` | Domínio de Atendimento |

**Relações principais** (todas em escopo único/central, não há separação central-vs-tenant pois não há multi-tenancy):

- `Atendimento` → `belongsTo(Cliente)`, `belongsTo(Canal)`, `belongsTo(User, 'assigned_to')`, `hasMany(AtendimentoMensagem)` (`app/Models/Atendimento/Atendimento.php`).
- `Canal` → `hasMany(Atendimento)`.
- `Cliente` → `hasMany(Atendimento)`.
- `AtendimentoMensagem` → `belongsTo(Atendimento)`, `belongsTo(User, 'autor_id')`.
- `User` → `hasOne(UserProfile)`, `hasMany(UserAddress)`, `hasMany(UserAdditionalData)`; usa `HasRoles` (Spatie) e `HasPushSubscriptions` (trait local, `app/Concerns/HasPushSubscriptions.php` — não lida integralmente, apenas referenciada).
- `MenuSideBar` → auto-relacionamento `parent`/`children` (menu hierárquico) com `scopeRoots`.

---

## 5. Autenticação e autorização

- **Login**: `app/Livewire/Auth/Login.php` + `app/Actions/Auth/AttemptLoginAction.php`. Rota `GET login` (`routes/auth.php`), guest-only.
- **Recuperação de senha**: `ForgotPassword`/`ResetPassword` (Livewire), fluxo padrão Laravel.
- **Verificação de e-mail**: `VerifyEmailNotice` (Livewire) + `VerifyEmailController` (controller tradicional), rota assinada (`signed`).
- **2FA**: TOTP via `pragmarx/google2fa`. Ações em `app/Actions/Auth/{Enable,Confirm,Disable,RegenerateRecoveryCodes,VerifyTwoFactorChallenge}Action.php`. Middleware `check2fa` (`app/Http/Middleware/CheckTwoFactor.php`) força desafio quando `two_factor_confirmed_at` existe e a sessão não tem `2fa_passed`, ou força configuração quando `requires_2fa=true` e ainda não confirmado.
- **RBAC**: `spatie/laravel-permission`, com `Role`/`Permission` locais em `app/Models` adicionando `Auditable`. `Gate::before` em `CoreServiceProvider` dá bypass total a quem tem role `admin`. Middleware `checkPermission:<permissão>` aplicado rota a rota em `routes/admin.php` e `routes/atendimento.php` (não há policy classes — `app/Policies` não existe no projeto, autorização é 100% via Gate/middleware + `$user->can()`).
- **Seeder de permissões** (`database/seeders/RolePermissionSeeder.php`): 17 permissions, 3 roles (`admin` com todas, `manager` parcial de leitura, `operator` operacional de atendimento/clientes).
- **Menu dinâmico**: `MenuSideBar` + `app/Livewire/Sidebar.php`, filtra itens pela permissão do usuário autenticado; `MenuSideBarSeeder` povoa os 16 itens citados pelo usuário (Dashboard, Notificações, Atendimento IA, Atendimentos, Clientes, Canais, IA e Chatbot, Relatórios, Usuários, Menu, Papéis, Permissões, Vínculo Papéis/Usuários, Configurações, Auditorias, Anúncios) — confirma que todos os nomes de menu citados pelo usuário existem de fato no seeder/código, não só no documento de planejamento.
- **Auditoria**: `owen-it/laravel-auditing`, trait `Auditable` em `User`, `AppConfig`, `MenuSideBar`, `Role`, `Permission`, `Atendimento`, `Canal`, `Cliente`, `ChatbotRegra` (não em `AtendimentoMensagem`, propositalmente, conforme `doc/04-atendimento-ia.md`). Eventos de login/logout auditados via `app/Listeners/Audit/*`. Tela admin: `app/Livewire/Admin/AuditManager.php`, rota `admin/audits`.

---

## 6. Funcionalidades por item de menu

| Item de menu (citado pelo usuário) | Rota | Componente | Status |
|---|---|---|---|
| Atendimento IA | `atendimento.dashboard` (`GET atendimento`) | `App\Livewire\Atendimento\AtendimentoDashboard` | **Funcional** — métricas reais (contagens por status, tendência 14 dias, breakdown por canal, satisfação média), sem dados mockados; consulta direto o banco (`app/Livewire/Atendimento/AtendimentoDashboard.php`) |
| Atendimentos | `atendimento.index` (`GET atendimento/lista`) | `App\Livewire\Atendimento\AtendimentoManager` + `AtendimentoShow` (detalhe, rota curinga `atendimento.show`) | **Funcional** como CRUD/listagem com filtros (confirmado teste `tests/Feature/Atendimento/AtendimentoManagerTest.php`); mensagens inseridas manualmente, sem canal real (ver Seção 7) |
| Clientes | `atendimento.clientes.index` | `App\Livewire\Atendimento\ClienteManager` | **Funcional** — CRUD de clientes (`clientes` table) |
| Canais | `atendimento.canais.index` | `App\Livewire\Atendimento\CanalManager` | **Funcional como CRUD**, mas **esqueleto de integração** — campo `configuracao` (JSON) existe no schema mas nenhuma integração o lê (confirmado lendo `CanalManager.php`: só grava `nome`/`tipo`/`ativo`) |
| IA e Chatbot | `atendimento.chatbot.index` | `App\Livewire\Atendimento\ChatbotManager` | **Funcional como MVP** — CRUD de `chatbot_regras` + `ChatbotEngine::responder()` faz match por `str_contains` de palavra-chave (`app/Services/Atendimento/ChatbotEngine.php:19-40`); não é um motor de IA, é busca de substring |
| Relatórios | `atendimento.relatorios.index` | `App\Livewire\Atendimento\Relatorios` | **Funcional** — breakdown por status/canal/setor com filtro de período; sem export (não implementado, conforme também o próprio `doc/04-atendimento-ia.md`) |
| Usuários | `admin.users.index` | `App\Livewire\Admin\UserManager` | **Funcional**, testado (`tests/Feature/Rbac/UserManagerTest.php`) |
| Menu | `admin.menu.index` | `App\Livewire\Admin\MenuSideBarManager` | **Funcional**, testado (`tests/Feature/Admin/MenuSideBarManagerTest.php`); valida rota existente e ícone Heroicons antes de salvar (`MenuSideBar::hasValidIcon()`) |
| Papéis | `admin.roles.index` | `App\Livewire\Admin\RoleManager` | **Funcional**, testado (`tests/Feature/Rbac/RoleManagerTest.php`) |
| Permissões | `admin.permissions.index` | `App\Livewire\Admin\PermissionManager` | **Funcional**, testado (`tests/Feature/Rbac/PermissionManagerTest.php`) |
| Vínculo Papéis/Usuários | `admin.roles-user.index` | `App\Livewire\Admin\RoleUserLinker` | **Funcional**, testado (`tests/Feature/Rbac/RoleUserLinkerTest.php`) |
| Configurações | `admin.config.index` | `App\Livewire\Admin\AppConfigManager` | **Funcional**, testado (`tests/Feature/Admin/AppConfigManagerTest.php`) |
| Auditorias | `admin.audits.index` | `App\Livewire\Admin\AuditManager` | **Funcional**, testado (`tests/Feature/Admin/AuditManagerTest.php`) |
| Anúncios | `admin.announcements.index` | `App\Livewire\Admin\AnnouncementManager` | **Funcional**, testado (`tests/Feature/Admin/AnnouncementManagerTest.php`); dispara `SystemAnnouncementNotification` pelos canais configurados |

Nenhum item de menu citado pelo usuário é "só aspiração do documento" — todos têm rota, componente Livewire e seeder correspondentes, verificados acima.

---

## 7. Integrações externas

- **Canais de mensagem (WhatsApp/Instagram/Facebook/E-mail)**: **não implementadas**. Não há nenhum SDK/cliente HTTP para essas plataformas em `composer.json`, nenhum controller de webhook de canal em `app/Http/Controllers`, nenhuma rota pública de recebimento de mensagem. A única forma de uma mensagem entrar no sistema é inserção manual pela tela de Atendimento (confirmado em `doc/04-atendimento-ia.md`, seção "Estado Atual", e ausência de qualquer `Webhook*Controller` no código).
- **Motor de IA / NanoClaw**: **não implementado**. `app/Services/Atendimento/ChatbotEngine.php` é um motor de regras por palavra-chave local, com comentário explícito no código apontando-o como "ponto de integração para o agente de IA (nanoclaw)" (linhas 9-16) — ou seja, a intenção de integrar está documentada no próprio código, mas nenhuma chamada de rede, SDK ou contrato de API para um serviço externo chamado NanoClaw existe no repositório.
- **Web Push**: implementado de fato — `minishlink/web-push`, `app/Notifications/Channels/WebPushChannel.php`, `app/Notifications/Messages/WebPushMessage.php`, model `PushSubscription`, variáveis `VAPID_SUBJECT`/`VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY` em `.env.example`.
- **Webhook channel (notificação)**: `app/Notifications/Channels/WebhookChannel.php` existe, mas é um canal de notificação de saída genérico do Core (não um webhook de canal de atendimento) — não verificado o destino/consumidor real desse canal (ver Lacunas).

---

## 8. Processamento assíncrono

- `jobs` table existe (migration padrão Laravel), `config/queue.php` tem `'default' => env('QUEUE_CONNECTION', 'database')`, mas **nenhuma classe no projeto implementa `ShouldQueue`** (busca por `ShouldQueue` em `app/` não retornou resultado) e **nenhum uso de `Queue::` facade** foi encontrado.
- Nenhum Job customizado (`app/Jobs` não existe).
- Nenhum agendamento customizado em `routes/console.php` (só o comando de exemplo `inspire` do skeleton Laravel).
- Notificações (`SystemAnnouncementNotification`, `UserAccountCreated`) são despachadas de forma síncrona (sem `ShouldQueue` na classe de notificação, não verificado em detalhe se alguma delas o implementa — ver Lacunas para confirmação linha a linha).

Conclusão: não há processamento assíncrono real em uso hoje, apesar da infraestrutura de fila padrão do Laravel estar presente (não configurada/usada).

---

## 9. API

**Não existe API.** `php artisan route:list` (rodado nesta auditoria) não lista nenhuma rota sob prefixo `/api`; não há arquivo `routes/api.php`; `laravel/sanctum` não está no `composer.json`. `bootstrap/app.php` define `shouldRenderJsonWhen` para requests `api/*` ou que esperam JSON, mas isso é só tratamento de exceção — não implica rotas de API existentes. Nada está pronto hoje para consumo externo (nem pelo futuro motor NanoClaw).

---

## 10. Configuração e deploy

- **Dockerfile** (`Dockerfile`, raiz do projeto): multi-stage — build de assets (Node 22 Alpine + `npm ci && npm run build`), dependências PHP (Composer, `--no-dev`), imagem final `php:8.4-fpm-alpine` com Nginx + Supervisor + extensões (`pdo_mysql`, `mbstring`, `bcmath`, `intl`, `zip`, `gd`, `opcache`).
- **Variáveis de ambiente** (nomes apenas, de `.env.example`): `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `APP_MAINTENANCE_DRIVER`, `BCRYPT_ROUNDS`, `LOG_CHANNEL`/`LOG_STACK`/`LOG_DEPRECATIONS_CHANNEL`/`LOG_LEVEL`, `DB_CONNECTION`/`DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD`, `SESSION_DRIVER`/`SESSION_LIFETIME`/`SESSION_ENCRYPT`/`SESSION_PATH`/`SESSION_DOMAIN`, `VAPID_SUBJECT`/`VAPID_PUBLIC_KEY`/`VAPID_PRIVATE_KEY`, `BROADCAST_CONNECTION`, `FILESYSTEM_DISK`, `QUEUE_CONNECTION`, `CACHE_STORE`/`CACHE_PREFIX`, `MEMCACHED_HOST`, `REDIS_CLIENT`/`REDIS_HOST`/`REDIS_PASSWORD`/`REDIS_PORT`, `MAIL_MAILER`/`MAIL_SCHEME`/`MAIL_HOST`/`MAIL_PORT`/`MAIL_USERNAME`/`MAIL_PASSWORD`/`MAIL_FROM_ADDRESS`/`MAIL_FROM_NAME`, `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`/`AWS_DEFAULT_REGION`/`AWS_BUCKET`/`AWS_USE_PATH_STYLE_ENDPOINT`, `VITE_APP_NAME`.
- **Dependências de serviço**: banco (SQLite em dev — `database/database.sqlite` presente; MySQL citado para produção nos docs e no Dockerfile via `mysql-client`); nenhuma dependência de Redis/Memcached confirmada em uso real (variáveis existem só como opção padrão do skeleton Laravel).
- `app/Providers/AppServiceProvider::boot()` força `URL::forceScheme('https')` em produção (commit `803c17f`), coerente com o uso atrás de proxy TLS (Easypanel, conforme commits recentes).
- `composer.json` tem script `setup` que roda `migrate --force` e `npm run build` — não executado nesta auditoria (seria mudança de estado).

---

## 11. Qualidade

- **Testes**: PHPUnit puro (não Pest, apesar de citado em `doc/senior-architect.md` — ver Seção 14), 27 arquivos de teste em `tests/Feature` + `tests/Unit/ExampleTest.php` + `tests/TestCase.php`. Cobertura aparente por área: Auth (4 testes), 2FA, Rbac (5), Admin (4), Atendimento (6, incluindo `ChatbotEngineTest`), Audit (2), Notifications/Push (3), Settings/Profile (1). Testes não foram executados nesta auditoria (proibido rodar comandos que mudam estado/ambiente); apenas a existência e os nomes dos arquivos foram verificados.
- **Padrões**: uso consistente de `#[Layout('components.layouts.master')]` nos componentes Livewire, separação Action/Service/Model razoavelmente disciplinada (`app/Actions/Auth`, `app/Services/Atendimento`).
- **TODOs/dívidas explícitas**: o próprio `doc/01-plano-implementacao.md` (Fase 8) e `doc/04-atendimento-ia.md` listam pendências conhecidas (export CSV/PDF de relatórios, notificar atendente via Core ao transferir atendimento, integrações de canal, motor de IA real) — dívidas documentadas, não escondidas.
- **Duplicação**: não verificado em profundidade (fora do escopo de tempo desta auditoria); não encontrada duplicação óbvia entre o módulo Admin e o módulo Atendimento durante a leitura dos arquivos abertos.
- Pasta `app/Repositories` existe mas seu conteúdo não foi lido nesta auditoria — ver Lacunas.

---

## 12. Segurança

- **Validação**: componentes Livewire usam `$this->validate([...])` com regras (ex.: `CanalManager::save()`, linha 44-48). Não verificado sistematicamente em todos os 22 componentes Livewire.
- **Autorização em rotas**: todas as rotas de admin e de atendimento (exceto dashboard/notifications/profile/push, que só exigem autenticação) estão atrás de `checkPermission:<permissão>` (`routes/admin.php`, `routes/atendimento.php`). Não há rotas admin "esquecidas" sem middleware de permissão, pelo que foi lido.
- **Exposição entre tenants**: não aplicável como "vazamento", pois não há isolamento de tenant implementado (ver Seção 3) — qualquer usuário autorizado por permissão vê todos os registros do sistema, não há fronteira de dados por estabelecimento.
- **Uploads**: `AppConfig.media_path` e `User.avatar_path` indicam upload de arquivos; implementação de validação/armazenamento desses uploads não foi lida em detalhe nesta auditoria (ver Lacunas).
- **Segredos no repositório**: `.env.example` contém apenas nomes de variáveis, sem valores — nenhum segredo encontrado nele. Não foi feita varredura completa por segredos hardcoded em todo o código-fonte (fora do escopo de tempo desta auditoria) — busca pontual não encontrou chaves/senhas em `app/Providers`, `config/queue.php`, `config/database.php`.
- `URL::forceScheme('https')` em produção (`AppServiceProvider`) é consistente com commit `803c17f` ("força HTTPS nas URLs geradas").

---

## 13. Código sem uso

- `optionalDependencies.@laravel/multiplex` em `package.json` — não verificado se é efetivamente usado em algum asset JS (candidato a dependência não usada; ver Lacunas).
- `pestphp/pest-plugin` aparece em `composer.json` → `config.allow-plugins`, mas o pacote `pestphp/pest` em si **não está** em `require-dev` — ou seja, o projeto está configurado para permitir o plugin Pest mas não o usa (os testes são PHPUnit puro, conforme Seção 11 e Seção 14). Possível resíduo de configuração.
- Não foi encontrado nenhum módulo, rota, tabela ou tela de domínio acadêmico — não há "código morto" desse tipo porque esse código nunca existiu neste repositório (ver Seção 14 para a reconciliação completa dessa hipótese do orquestrador).
- `app/Repositories` — pasta existe; não verificado se tem arquivos ou está vazia/sem uso (ver Lacunas).

---

## 14. Documentação vs código

**Resumo de 2 linhas por documento:**

- `doc/00-instalacao-fase0.md` — Guia de instalação/setup do ambiente (Fase 0). Descreve passos genéricos de `composer create-project`/configuração de `.env`; não é uma especificação de funcionalidade, por isso não gera itens de comparação ponto-a-ponto com código.
- `doc/01-plano-implementacao.md` — Plano de fases (0 a 9) do projeto, com checklist de cada fase marcado como concluído ou pendente pelos próprios autores. É o documento mais diretamente verificável contra o código, e está bem alinhado (ver tabela abaixo).
- `doc/02-core.md` — Especificação do módulo Core (auth, RBAC, menu, config, notificações, auditoria). Descreve o Core como "implementado e testado"; confirmado pelo código e pelos testes existentes.
- `doc/03-ui-system.md` — Convenções de stack de UI (Livewire/Blade/Alpine/Tailwind) e tokens de design. Não descreve funcionalidade de negócio, portanto comparável apenas quanto à stack (confirmada: Livewire 4, Tailwind v4, Alpine via Livewire — todos presentes em `composer.json`/`package.json`).
- `doc/04-atendimento-ia.md` — Especificação do domínio de Atendimento com IA, já com seção "Estado Atual" e checklist "Funcionalidades Planejadas" marcando explicitamente o que está pendente (integrações de canal, motor de IA real, export de relatórios, notificação de transferência). Altamente alinhado ao código — os próprios autores documentaram as lacunas que esta auditoria também encontrou de forma independente.
- `doc/05-cutover.md` — Checklist de go-live, genérico (itens `[ ]` não marcados, template a ser preenchido por feature). Não afirma fatos sobre o código, por isso não há divergência a apontar.
- `doc/senior-architect.md` — Documento de diretrizes de arquitetura sênior (skill), não um relatório de estado do projeto. Contém a única divergência relevante encontrada: lista "Laravel Sanctum" e "Pest PHP" como parte da stack de referência, mas nenhum dos dois está instalado no `composer.json` do projeto (ver tabela abaixo).

**Tabela item planejado vs código:**

| Item planejado | Documento | Situação no código | Evidência (arquivo) |
|---|---|---|---|
| Laravel Sanctum para auth de API | `doc/senior-architect.md` (tabela de stack) | **Não implementado** — pacote ausente, sem rotas de API | `composer.json` (sem `laravel/sanctum`); `php artisan route:list` sem `/api` |
| Pest PHP para testes | `doc/senior-architect.md` (tabela de stack) | **Não implementado** — testes são PHPUnit padrão (`extends TestCase`, não `Pest`) | `composer.json` (`phpunit/phpunit` em require-dev, sem `pestphp/pest`); arquivos em `tests/Feature/*` usam sintaxe de classe PHPUnit |
| Fase 8 (Atendimento com IA) "infraestrutura concluída, integrações pendentes" | `doc/01-plano-implementacao.md` | **Confirmado igual ao documentado**: painel funcional, integrações de canal e motor de IA real ausentes | `app/Services/Atendimento/ChatbotEngine.php`, `app/Livewire/Atendimento/*`, ausência de controllers de webhook |
| `configuracao` JSON em Canais pronta para credenciais de API, mas nenhuma integração a lê | `doc/04-atendimento-ia.md` | **Confirmado** — `CanalManager::save()` nunca grava/lê o campo `configuracao` | `app/Livewire/Atendimento/CanalManager.php:42-57`; migration `2026_08_19_054849_create_canais_table.php` |
| Notificar atendente via Core ao transferir atendimento (gap conhecido) | `doc/04-atendimento-ia.md` | **Confirmado como gap** — nenhuma chamada a `Notification::send`/`notify()` encontrada em `ChatbotEngine` ou nos componentes de Atendimento ligada a transferência de setor | `app/Services/Atendimento/ChatbotEngine.php` (não despacha notificação) |
| Export de relatórios CSV/PDF | `doc/04-atendimento-ia.md` | **Não implementado**, confirmado | `app/Livewire/Atendimento/Relatorios.php` (sem método de export) |
| Fase 9 — Go-Live | `doc/01-plano-implementacao.md`, `doc/05-cutover.md` | **Não concluída** — checklist com itens `[ ]` não marcados em ambos os documentos | `doc/01-plano-implementacao.md` linhas 180-183; `doc/05-cutover.md` (todos os itens `[ ]`) |
| Canal `mail` substituído por `webhook` (nota de produto) | `doc/01-plano-implementacao.md` (Fase 5) | **Confirmado** — existe `WebhookChannel`, não há uso de canal `mail` em `app/Notifications` | `app/Notifications/Channels/WebhookChannel.php`; `app/Notifications/SystemAnnouncementNotification.php` (não lido linha a linha para confirmar os `via()` exatos — ver Lacunas) |

**Fase real do plano vs o que o plano previa para o módulo de Atendimento com IA:** o plano (`doc/01-plano-implementacao.md`) descreve a Fase 8 como "🟡 Infraestrutura concluída, integrações pendentes" e esta auditoria confirma exatamente esse estado — não há otimismo não comprovado nem pessimismo não documentado: a autoavaliação dos autores do projeto bate com o que o código mostra. A Fase 9 (Go-Live) não foi iniciada.

**Sobre a hipótese do orquestrador (módulo acadêmico / bifurcação de domínio):** não confirmada. Não há nenhum vestígio de código, migration, rota, model ou teste relacionado a Curso/Turma/Módulo/Conteúdo/matrícula/progresso de aluno neste repositório. Os nomes de arquivo `02-core.md` e `03-ui-system.md` já correspondem, no conteúdo lido, ao domínio Core/UI deste mesmo projeto de atendimento — não há indício de que esses nomes tenham sido reaproveitados de um domínio escolar.

---

## Inventário de funcionalidades

| Funcionalidade | Onde está (arquivos) | Status | Observação |
|---|---|---|---|
| Login / 2FA / recuperação de senha | `app/Livewire/Auth/*`, `app/Actions/Auth/*`, `app/Http/Middleware/CheckTwoFactor.php` | Funcional | Testado em `tests/Feature/Auth/*`, `tests/Feature/Auth/TwoFactorTest.php` |
| RBAC (roles/permissions) | `app/Models/Role.php`, `Permission.php`, `app/Http/Middleware/CheckPermission.php`, `database/seeders/RolePermissionSeeder.php` | Funcional | Gate bypass para `admin` em `CoreServiceProvider` |
| Menu dinâmico | `app/Models/MenuSideBar.php`, `app/Livewire/Sidebar.php`, `app/Livewire/Admin/MenuSideBarManager.php` | Funcional | Valida ícone Heroicons e nome de rota antes de salvar |
| App Config | `app/Models/AppConfig.php`, `app/Livewire/Admin/AppConfigManager.php`, seeder `AppConfigSeeder.php` | Funcional | Usado para nome/logo/avatar padrão, conforme doc |
| Notificações in-app/push/webhook | `app/Livewire/NotificationBell.php`, `NotificationList.php`, `app/Notifications/*`, `app/Models/PushSubscription.php` | Funcional | Testado (`NotificationBellTest`, `NotificationListTest`, `PushSubscriptionTest`) |
| Anúncios (broadcast) | `app/Livewire/Admin/AnnouncementManager.php`, `app/Models/SystemAnnouncement.php` | Funcional | Testado |
| Auditoria | `app/Livewire/Admin/AuditManager.php`, trait `Auditable` nos models, `app/Listeners/Audit/*` | Funcional | Exclui `AtendimentoMensagem` por volume (decisão documentada) |
| Perfil do usuário | `app/Livewire/Settings/Profile.php`, `UserProfile`/`UserAddress`/`UserAdditionalData` | Funcional | Testado (`ProfileTest.php`) |
| Dashboard de Atendimento | `app/Livewire/Atendimento/AtendimentoDashboard.php` | Funcional | Métricas reais calculadas em tempo real via Eloquent |
| Listagem/detalhe de Atendimentos | `AtendimentoManager.php`, `AtendimentoShow.php` | Funcional | Mensagens só inseridas manualmente/simuladas (sem canal real) |
| Clientes | `ClienteManager.php` | Funcional | CRUD simples |
| Canais | `CanalManager.php` | Funcional como CRUD / esqueleto de integração | Campo `configuracao` não é lido por nenhuma integração |
| IA e Chatbot | `ChatbotManager.php`, `ChatbotEngine.php` | MVP funcional, não é IA real | Match por substring de palavra-chave |
| Relatórios | `Relatorios.php` | Funcional (sem export) | Export CSV/PDF não implementado |
| Integração WhatsApp/Instagram/Facebook/E-mail | — | **Inexistente** | Nenhum arquivo encontrado |
| Motor de IA externo (NanoClaw) | — | **Inexistente** | Apontado só como comentário de intenção em `ChatbotEngine.php` |
| API pública | — | **Inexistente** | Sem `routes/api.php`, sem Sanctum |
| Filas/Jobs assíncronos | `jobs` table (não usada) | **Sem uso** | Nenhuma classe `ShouldQueue` no projeto |
| Multi-tenancy | — | **Inexistente** | Ver Seção 3 |

---

## Entidades e tabelas

| Tabela | Escopo | Para que serve | Usada por |
|---|---|---|---|
| `users` | Central (único) | Conta de usuário, 2FA, auth | `User` model, Auth, RBAC |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Central | RBAC Spatie | `Role`, `Permission`, middleware `checkPermission` |
| `menu_side_bars` | Central | Itens de menu lateral dinâmico, hierárquico | `MenuSideBar`, `Sidebar`, `MenuSideBarManager` |
| `app_configs` | Central | Configurações chave/valor (com mídia) | `AppConfig`, `AppConfigManager` |
| `audits` | Central | Trilha de auditoria (pacote owen-it) | Todos os models `Auditable` |
| `notifications` | Central | Notificações polimórficas padrão Laravel | `NotificationBell`, `NotificationList` |
| `push_subscriptions` | Central | Assinaturas Web Push por usuário | `PushSubscription`, canal `WebPushChannel` |
| `system_announcements` | Central | Anúncios enviados pelo admin | `SystemAnnouncement`, `AnnouncementManager` |
| `user_profiles`, `user_addresses`, `user_additional_data` | Central | Dados estendidos de perfil do usuário | `Profile` (Livewire) |
| `clientes` | Central (sem isolamento por estabelecimento) | Cadastro de clientes atendidos | `Cliente`, `ClienteManager`, `Atendimento` |
| `canais` | Central | Canais de atendimento (tipo, ativo, config JSON não usada) | `Canal`, `CanalManager` |
| `atendimentos` | Central | Registro de cada atendimento (status, setor, satisfação) | `Atendimento`, `AtendimentoManager`, `AtendimentoShow`, `AtendimentoDashboard`, `Relatorios` |
| `atendimento_mensagens` | Central | Mensagens dentro de um atendimento | `AtendimentoMensagem`, `AtendimentoShow` |
| `chatbot_regras` | Central | Regras de resposta automática por palavra-chave | `ChatbotRegra`, `ChatbotManager`, `ChatbotEngine` |

---

## Lacunas e dúvidas

- **Branch solicitada não encontrada**: o pedido mencionava branch `schoolteacher`; o repositório local só tem `master`. Não verificado se essa branch existe remotamente (não foi feito `git fetch`/`ls-remote` para não alterar nada do ambiente local além do permitido). Pergunta ao dono: a auditoria deveria ter sido feita em outra branch/remote?
- **`app/Repositories`**: pasta existe mas seu conteúdo não foi lido em detalhe nesta auditoria — não verificado se há algo nela ou se está vazia/sem uso.
- **Uploads de arquivo** (`avatar_path`, `media_path` do AppConfig): implementação de validação de tipo/tamanho e armazenamento não foi lida linha a linha — não verificado se há risco de upload de arquivo arbitrário.
- **`app/Notifications/SystemAnnouncementNotification.php` e `UserAccountCreated.php`**: não lidos integralmente; não confirmado com certeza absoluta se algum implementa `ShouldQueue` (impacto na afirmação da Seção 8) nem quais canais (`via()`) cada um usa de fato.
- **Testes não executados**: por restrição desta auditoria (somente leitura, proibido rodar comandos que mudam estado), não foi possível confirmar se os 27+ arquivos de teste realmente passam hoje — apenas sua existência e nomes foram verificados.
- **Segredos no repositório**: não foi feita uma varredura automatizada completa (tipo grep por padrões de chave/token) em todo o código; só inspeção pontual de arquivos de configuração considerados mais prováveis.
- **`@laravel/multiplex`** (package.json, optionalDependencies): não verificado se é referenciado em algum asset JS do projeto — candidato a dependência não usada, não confirmado.
- **Duplicação de código entre módulos**: não houve tempo de comparar os 22 componentes Livewire par a par em busca de lógica repetida (ex.: padrões de modal/CRUD) — apontado como possível trabalho futuro de auditoria, não investigado aqui.
- **Pergunta ao dono**: o objetivo futuro de multi-tenant (vários estabelecimentos, vários agentes por estabelecimento) exige decisão arquitetural que hoje não tem nenhum ponto de partida no código (nem coluna nullable, nem flag) — vale confirmar se o dono prefere migração incremental (adicionar `estabelecimento_id` nas tabelas existentes) ou um redesenho mais amplo antes de iniciar a integração com o NanoClaw.
- **Pergunta ao dono**: `configuracao` (JSON) em `canais` já existe no schema mas não é usada — confirmar se a intenção é reaproveitar esse campo para credenciais de API dos canais reais, ou se será redesenhado.
