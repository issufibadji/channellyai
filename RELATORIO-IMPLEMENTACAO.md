# Relatório de Implementação — Core + Domínio Acadêmico

**Stack:** Laravel 13 · Livewire 4 (class-based) · Tailwind CSS · Spatie Permission — dashboard dark theme, padrão de Managers, RBAC ponta a ponta.

**Status:** 108/108 testes passando (205 assertions), branch `schoolteacher`, working tree limpo.

---

## 1. Core — Fases 0 a 7

### Fase 0-1 · Setup & Autenticação
- Login, logout, recuperação de senha, verificação de e-mail
- 2FA via TOTP (Google Authenticator) — habilitar, confirmar, desabilitar, códigos de recuperação
- Rate limiting no login (6 tentativas/min)
- Flags `active` / `requires_2fa` por usuário

### Fase 2 · RBAC
- `UserManager`, `RoleManager`, `PermissionManager`, `RoleUserLinker`
- Models `Role`/`Permission` próprios (estendem Spatie)
- Middleware `checkPermission:xxx` por rota
- `Gate::before` — admin passa em qualquer checagem

### Fase 3 · UI & Menu
- Design system dark (tokens de cor, componentes `<x-button>`/`<x-card>`/`<x-modal>`/`<x-table>`)
- Sidebar dinâmica (`menu_side_bars`), filtrada por permissão
- `MenuSideBarManager` — CRUD do menu pelo admin

### Fase 4 · App Config
- `AppConfigManager` + `AppConfigService` (cache)
- Upload de mídia (disco `public`), helper `config_app('chave')`

### Fase 5 · Notificações
- Canal banco — sino no topbar, contador, marcar lida
- Web Push real (VAPID, `minishlink/web-push` — sem o wrapper Laravel, que não suporta L13)
- Canal Webhook (URL configurável via App Config)
- `AnnouncementManager` — broadcast pra todos ou por papel

### Fase 6 · Perfil
- Avatar com corte de imagem, dados adicionais, endereços
- `UserProfile`, `UserAddress`, `UserAdditionalData`

### Fase 7 · Auditoria
- `owen-it/laravel-auditing` — `created`/`updated`/`deleted` automático
- Aplicado em `User`, `AppConfig`, `MenuSideBar`
- Senha e segredos de 2FA excluídos do log

---

## 2. Domínio Acadêmico — Fase 8 (implementado nesta sessão)

Hierarquia `Curso → Turma → Módulo → Conteúdo`, com escopo de dados por perfil (admin / professor / aluno) reforçado em duas camadas: query scope + Policy.

### Tabelas
| Tabela | Papel |
|---|---|
| `cursos` | Curso oferecido (longo/curto prazo) |
| `turmas` | Turma de um curso, com professor responsável |
| `turma_aluno` | Matrícula — pivô com `unique(turma_id, aluno_id)` |
| `modulos` | Nível (A1-C2) dentro de uma turma, ordenado |
| `conteudos` | Vídeo/PDF/texto/exercício/link dentro de um módulo |

### Componentes por perfil
- **Admin:** `CursoManager` (CRUD cursos), `TurmaManager` (CRUD turmas, seleciona professor)
- **Professor:** `MinhasTurmas`, `ModuloConteudoManager` (CRUD aninhado módulo+conteúdo, escopado), `MatriculaManager` (matricular/desmatricular), `AlunoManager` (só alunos das próprias turmas)
- **Aluno:** `MinhasTurmasDoAluno` — **lista**, não tela singular, porque um aluno pode estar matriculado em mais de uma turma ao mesmo tempo (ex.: inglês geral + business English em paralelo). `Turma::scopeDoAluno()` devolve todas as turmas, com módulos e conteúdos liberados de cada uma.

### Defesa em duas camadas
```
Query scope (Turma::doProfessor / doAluno)
  → decide o que aparece na listagem

Policy (TurmaPolicy / ModuloPolicy / ConteudoPolicy)
  → reforça objeto-a-objeto, mesmo se alguém tentar acessar
    /academico/turmas/{id}/conteudo digitando a URL direto

checkPermission:manage-own-turmas (rota)
  → decide se o papel pode acessar a TELA, não a linha específica
```

---

## 3. Roles & Permissions

| Role | Permissions próprias | Observação |
|---|---|---|
| `admin` | todas (14) | + bypass via `Gate::before` |
| `manager` | `view-users` | genérica, herdada do Core |
| `operator` | — | placeholder, sem permissions ainda |
| `professor` | `manage-own-turmas` | nova — Fase 8 |
| `aluno` | `view-own-turma` | nova — Fase 8 |

---

## 4. Cobertura de testes

PHPUnit class-based (padrão real do projeto — não Pest). 108 testes, 205 assertions, 0 falhas.

| Área | Testes |
|---|---|
| Autenticação, 2FA, RBAC, Menu, Config, Notificações, Auditoria (Core) | 82 |
| Escopo Curso/Turma/Módulo/Conteúdo (`TurmaScopeTest`) | 11 |
| CRUDs de Curso/Turma, Matrícula, Meus Alunos, Minhas Turmas (professor/aluno) | 15 |

**Regra mais importante testada:** professor A não vê nem edita turma, módulo, conteúdo ou aluno da turma do professor B — validado tanto pelo *scope* quanto por um 403 real de *Policy* na rota.

---

## 5. Pendências e divergências conhecidas

- **Web Push moderno force-instalado** — `minishlink/web-push` foi puxado com `-W` porque o wrapper oficial do Laravel ainda não suporta L13. Baixo risco (só um downgrade de `brick/math`), mas vale revisitar quando o wrapper atualizar.
- **Sem drag-and-drop** — ordenação de menu, módulos e conteúdos é por campo numérico, não arrastar-e-soltar.
- **Relatórios do domínio acadêmico** — frequência/progresso por turma (citado no doc) ainda não tem tela própria.
- **Ambiente Windows** — `php` do PATH resolve pro XAMPP (8.2) em vez do Herd (8.4); use o binário completo do Herd ou corrija a ordem do PATH (documentado no `00-instalacao-fase0.md`). `OPENSSL_CONF` também precisa apontar pro `openssl.cnf` do Herd pra VAPID funcionar.

---

## 6. Mapa de arquivos — Domínio Acadêmico

```
app/
├── Models/{Curso,Turma,Modulo,Conteudo}.php
├── Policies/{Turma,Modulo,Conteudo}Policy.php
└── Livewire/Academico/
    ├── CursoManager.php · TurmaManager.php
    ├── MinhasTurmas.php · MinhasTurmasDoAluno.php
    ├── ModuloConteudoManager.php
    ├── MatriculaManager.php
    └── AlunoManager.php

database/
├── migrations/  (cursos, turmas, turma_aluno, modulos, conteudos)
├── factories/{Curso,Turma,Modulo,Conteudo}Factory.php
└── seeders/AcademicoPermissionSeeder.php

routes/academico.php
tests/Feature/Academico/  (7 arquivos, 26 testes)
```
