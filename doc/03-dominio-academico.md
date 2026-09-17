# 03 — Domínio Acadêmico (Curso / Turma / Módulo / Conteúdo)

Este documento modela a área de negócio do sistema: o que o Core **não** resolve. Cobre a hierarquia acadêmica (Curso → Turma → Módulo → Conteúdo), a matrícula de alunos, o vínculo de professores às turmas, e as regras de escopo de dados por perfil (quem enxerga o quê).

Pré-requisito: o Core (autenticação, RBAC, menu, notificações, perfil, auditoria — ver `02-core.md`) já deve estar implementado, pois este domínio depende de `User`, `Role`/`Permission` e do padrão de Managers/Actions já estabelecido no Core.

---

## Propósito deste Domínio

Resolve as perguntas específicas do negócio "escola de inglês":

- Quais cursos existem, e são de curto ou longo prazo?
- Um curso se divide em quais turmas?
- Quem é o professor responsável por cada turma?
- Quais alunos estão matriculados em cada turma?
- Como o conteúdo de uma turma é organizado por nível (A1, A2, B1, B2...)?
- O que cada módulo efetivamente contém (aula, PDF, vídeo, exercício)?
- O que um professor pode ver/gerenciar (só as turmas e alunos dele) versus o que um aluno pode ver (só a própria turma e os conteúdos liberados)?

---

## Hierarquia de Dados

```
Curso
 └── Turma (N por curso)
      ├── Professor responsável (1 por turma)
      ├── Alunos matriculados (N, via matrícula)
      └── Módulo (N por turma, ordenado, com nível)
           └── Conteúdo (N por módulo, ordenado) ← o conteúdo fica DENTRO do módulo,
                                                     não é uma entidade irmã dele
```

**Pontos fechados na definição do domínio:**

1. **Conteúdo é filho de Módulo** — um módulo não é só um rótulo de nível, ele agrupa um ou mais conteúdos (vídeo, PDF, texto, exercício, link).
2. **Aluno só enxerga a própria turma** — dashboard do aluno mostra a turma em que está matriculado e os conteúdos liberados dela (módulos/conteúdos de outras turmas não aparecem).
3. **Professor só gerencia os alunos das turmas dele** — a listagem de "Usuários" na visão do professor é escopada por `turma.professor_id = auth()->id()`; ele não vê nem edita alunos de turmas de outros professores.

---

## Modelagem de Tabelas

### `cursos`
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `nome` | string | |
| `tipo` | enum(`longo_prazo`,`curto_prazo`) | |
| `descricao` | text nullable | |
| `ativo` | boolean default true | |
| `timestamps` | | |

### `turmas`
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `curso_id` | FK → `cursos` | |
| `professor_id` | FK → `users` | professor responsável pela turma |
| `nome` | string | ex.: "Turma 1", "Turma Manhã" |
| `data_inicio` | date nullable | |
| `data_fim` | date nullable | |
| `ativo` | boolean default true | |
| `timestamps` | | |

### `turma_aluno` (pivô de matrícula)
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `turma_id` | FK → `turmas` | |
| `aluno_id` | FK → `users` | |
| `data_matricula` | date | |
| `status` | enum(`ativo`,`trancado`,`concluido`) default `ativo` | |
| `timestamps` | | |
| — | unique(`turma_id`,`aluno_id`) | evita matrícula duplicada |

### `modulos`
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `turma_id` | FK → `turmas` | |
| `nome` | string | |
| `nivel` | enum(`A1`,`A2`,`B1`,`B2`,`C1`,`C2`) | |
| `ordem` | integer default 0 | ordenação de exibição |
| `timestamps` | | |

### `conteudos`
| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint PK | |
| `modulo_id` | FK → `modulos` | |
| `titulo` | string | |
| `tipo` | enum(`video`,`pdf`,`texto`,`exercicio`,`link`) | |
| `corpo` | text nullable | texto livre, quando `tipo=texto` |
| `arquivo_path` | string nullable | quando `tipo=pdf`/`video` |
| `url_externa` | string nullable | quando `tipo=link` |
| `ordem` | integer default 0 | |
| `timestamps` | | |

---

## Models e Relacionamentos

```php
// app/Models/Curso.php
class Curso extends Model
{
    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }
}

// app/Models/Turma.php
class Turma extends Model
{
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function alunos(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'turma_aluno', 'turma_id', 'aluno_id')
            ->withPivot(['data_matricula', 'status'])
            ->withTimestamps();
    }

    public function modulos(): HasMany
    {
        return $this->hasMany(Modulo::class)->orderBy('ordem');
    }

    // Escopo: turmas do professor autenticado
    public function scopeDoProfessor(Builder $query, User $professor): Builder
    {
        return $query->where('professor_id', $professor->id);
    }

    // Escopo: turmas em que o usuário está matriculado
    public function scopeDoAluno(Builder $query, User $aluno): Builder
    {
        return $query->whereHas('alunos', fn ($q) => $q->where('users.id', $aluno->id));
    }
}

// app/Models/Modulo.php
class Modulo extends Model
{
    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function conteudos(): HasMany
    {
        return $this->hasMany(Conteudo::class)->orderBy('ordem');
    }
}

// app/Models/Conteudo.php
class Conteudo extends Model
{
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }
}
```

---

## Escopo de Dados por Perfil (regra central deste domínio)

Seguindo o padrão de **Managers** já estabelecido no Core (`UserManager`, `RoleManager`, etc.), este domínio introduz managers próprios que já nascem cientes do escopo — a UI nunca decide o escopo, o Manager decide:

```php
// app/Managers/TurmaManager.php
class TurmaManager
{
    // Usado pela tela "Minhas Turmas" do professor
    public function paraProfessor(User $professor): Collection
    {
        return Turma::doProfessor($professor)->with('curso', 'modulos')->get();
    }

    // Usado pelo dashboard do aluno
    public function paraAluno(User $aluno): ?Turma
    {
        return Turma::doAluno($aluno)->with('modulos.conteudos')->first();
    }
}

// app/Managers/AlunoManager.php (usado na tela "Usuários" do professor)
class AlunoManager
{
    public function daTurmaDoProfessor(User $professor): Collection
    {
        return User::whereHas('turmasMatriculadas', function ($q) use ($professor) {
            $q->where('professor_id', $professor->id);
        })->get();
    }
}
```

**Reforço via Policy** (defesa em profundidade — não depender só do Manager filtrar certo):

```php
// app/Policies/TurmaPolicy.php
class TurmaPolicy
{
    public function view(User $user, Turma $turma): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('professor')) return $turma->professor_id === $user->id;
        if ($user->hasRole('aluno')) return $turma->alunos()->where('users.id', $user->id)->exists();
        return false;
    }

    public function manageAlunos(User $user, Turma $turma): bool
    {
        return $user->hasRole('admin') || $turma->professor_id === $user->id;
    }
}
```

---

## O que Cada Perfil Vê (consolidado das conversas anteriores)

| Área | Admin | Professor | Aluno |
|---|---|---|---|
| Cursos/Turmas | Gerencia todos | Vê só as turmas onde é `professor_id` | Vê só a turma em que está matriculado |
| Módulos/Conteúdos | Gerencia todos | CRUD nos módulos/conteúdos das próprias turmas | Somente leitura, só da própria turma |
| Usuários (alunos) | Todos | Só os alunos matriculados nas próprias turmas | — |
| Relatório | Todos | Das próprias turmas | — |
| Anúncio | Cria/gerencia | Recebe (e pode criar para a própria turma, a definir) | Recebe |
| Perfil | Próprio | Próprio | Próprio |
| Ajuda | Sim | Sim | Sim |

---

## O que NÃO Pertence a Este Domínio

| O que não vai | Onde vai |
|---|---|
| Autenticação, 2FA, sessão | Core (`02-core.md`) |
| Roles/Permissions em si (a definição do que é um "professor") | Core — RBAC |
| Menu/Sidebar | Core |
| Notificações genéricas, Web Push | Core |
| Perfil do usuário (dados pessoais, endereço, avatar) | Core |
| Auditoria genérica | Core |

Este domínio só define **o que é ensinado e para quem** — não como o usuário se autentica nem como o sistema registra logs.

---

## Namespace e Estrutura de Arquivos (seguindo o padrão do Core)

```
app/
├── Models/
│   ├── Curso.php
│   ├── Turma.php
│   ├── Modulo.php
│   └── Conteudo.php
├── Managers/
│   ├── CursoManager.php
│   ├── TurmaManager.php
│   ├── ModuloManager.php
│   ├── ConteudoManager.php
│   └── AlunoManager.php
├── Policies/
│   ├── TurmaPolicy.php
│   ├── ModuloPolicy.php
│   └── ConteudoPolicy.php
└── Livewire/
    └── Academico/
        ├── CursoList.php / CursoForm.php
        ├── TurmaList.php / TurmaForm.php
        ├── MinhasTurmas.php          ← visão do professor
        ├── MinhaTurma.php            ← visão do aluno (singular — só uma)
        ├── ModuloManager.php (Livewire component de CRUD de módulos+conteúdo)
        └── MatriculaManager.php      ← vincular/desvincular alunos de uma turma

database/
└── migrations/
    ├── create_cursos_table.php
    ├── create_turmas_table.php
    ├── create_turma_aluno_table.php
    ├── create_modulos_table.php
    └── create_conteudos_table.php
```

---

## Funcionalidades Planejadas

### Cursos e Turmas
- [ ] Migration `cursos`
- [ ] Migration `turmas`
- [ ] Migration `turma_aluno` (matrícula)
- [ ] Model `Curso`, `Turma` com relacionamentos e scopes (`doProfessor`, `doAluno`)
- [ ] `CursoManager`, `TurmaManager`
- [ ] Tela admin: CRUD de Cursos e Turmas (com seleção de professor responsável)
- [ ] Tela "Minhas Turmas" (professor)
- [ ] `TurmaPolicy`

### Módulos e Conteúdo
- [ ] Migration `modulos`
- [ ] Migration `conteudos`
- [ ] Model `Modulo`, `Conteudo`
- [ ] `ModuloManager`, `ConteudoManager`
- [ ] Tela de CRUD de módulos + conteúdo aninhado (professor, escopado à própria turma)
- [ ] Upload de arquivo para conteúdo tipo `pdf`/`video`
- [ ] `ModuloPolicy`, `ConteudoPolicy`

### Matrícula
- [ ] `AlunoManager::daTurmaDoProfessor()`
- [ ] Tela "Usuários" do professor (escopada — só alunos das próprias turmas)
- [ ] Componente Livewire `MatriculaManager` (vincular/desvincular aluno ↔ turma)

### Visão do Aluno
- [ ] `TurmaManager::paraAluno()`
- [ ] Dashboard do aluno mostrando turma + progresso nos módulos
- [ ] Tela "Minha Turma" (somente leitura, módulos + conteúdos liberados)

### Relatórios (escopados)
- [ ] Relatório de turma para o professor (frequência, progresso — a detalhar)
- [ ] Relatório geral para o admin

---

## Stack de Referência

Mesma stack do Core — este domínio não introduz dependências novas:

- **Laravel 13**
- **Livewire 3/4** (class-based, `--class`)
- **Spatie Permission** — roles `admin`, `professor`, `aluno` já devem existir via seeder do Core
- **Tailwind CSS**
