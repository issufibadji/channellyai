<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class Turma extends Model
{
    use HasFactory;

    protected $fillable = ['curso_id', 'professor_id', 'nome', 'data_inicio', 'data_fim', 'ativo'];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'ativo' => 'boolean',
        ];
    }

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

    /**
     * Escopo: turmas em que o usuário é o professor responsável.
     */
    public function scopeDoProfessor(Builder $query, User $professor): Builder
    {
        return $query->where('professor_id', $professor->id);
    }

    /**
     * Escopo: turmas em que o usuário está matriculado como aluno.
     * Um aluno pode estar em mais de uma turma simultaneamente — quem
     * chama decide se quer ->get() (lista) ou ->first().
     */
    public function scopeDoAluno(Builder $query, User $aluno): Builder
    {
        return $query->whereHas('alunos', fn ($q) => $q->where('users.id', $aluno->id));
    }

    /**
     * Total de aulas "de nível" da turma (ignora módulos categoria=extra).
     */
    public function totalAulas(): int
    {
        return Conteudo::whereHas(
            'modulo',
            fn ($q) => $q->where('turma_id', $this->id)->where('categoria', 'nivel'),
        )->count();
    }

    /**
     * Quantas dessas aulas de nível o aluno já concluiu.
     */
    public function aulasConcluidasPor(User $aluno): int
    {
        return AlunoProgresso::where('aluno_id', $aluno->id)
            ->whereHas(
                'conteudo.modulo',
                fn ($q) => $q->where('turma_id', $this->id)->where('categoria', 'nivel'),
            )
            ->count();
    }

    /**
     * Percentual concluído pelo aluno (0 quando a turma não tem aulas
     * de nível ainda, pra não dividir por zero).
     */
    public function percentualConcluido(User $aluno): float
    {
        $total = $this->totalAulas();

        return $total === 0 ? 0.0 : round($this->aulasConcluidasPor($aluno) / $total * 100, 1);
    }

    /**
     * Primeiro conteúdo disponível e ainda não concluído pelo aluno,
     * nesta turma. Sem memória de progresso — recalcula toda vez.
     *
     * @return array{status: 'proximo'|'tudo-concluido'|'sem-disponivel', url: string, conteudo?: Conteudo}
     */
    public function proximoConteudoDisponivelPara(User $aluno): array
    {
        $diasDesdeMatricula = null;
        $existeDisponivel = false;

        foreach ($this->modulos()->with('conteudos')->get() as $modulo) {
            foreach ($modulo->conteudos as $conteudo) {
                $diasDesdeMatricula ??= $conteudo->diasDesdeMatricula($aluno, $this);

                if ($conteudo->disponivelPara($aluno, $this, $diasDesdeMatricula)) {
                    $existeDisponivel = true;

                    if (! $conteudo->concluidoPor($aluno)) {
                        return [
                            'status' => 'proximo',
                            'url' => route('academico.minha-turma.aula', [$this, $conteudo]),
                            'conteudo' => $conteudo,
                        ];
                    }
                }
            }
        }

        return [
            'status' => $existeDisponivel ? 'tudo-concluido' : 'sem-disponivel',
            'url' => route('academico.minha-turma.turma', $this),
        ];
    }

    /**
     * Consulta base da trilha: só módulos categoria=nivel (Atividades Extras
     * nunca entra), ordenada por nível do módulo, ordem do módulo, ordem do
     * conteúdo. A1..C2 ordena certo como string simples.
     */
    private function consultaSequencia(): Builder
    {
        return Conteudo::query()
            ->join('modulos', 'modulos.id', '=', 'conteudos.modulo_id')
            ->where('modulos.turma_id', $this->id)
            ->where('modulos.categoria', 'nivel')
            ->orderBy('modulos.nivel')
            ->orderBy('modulos.ordem')
            ->orderBy('conteudos.ordem')
            ->select('conteudos.*');
    }

    /**
     * @return Collection<int, Conteudo>
     */
    public function sequenciaConteudos(): Collection
    {
        return $this->consultaSequencia()->get();
    }

    /**
     * Conteúdo anterior/próximo relativo a $conteudo, DENTRO da mesma seção
     * (ex.: "Vídeos Curtos" não se mistura com "Mapas Mentais" do mesmo
     * nível). Ambos null nas pontas, e também se $conteudo não pertence à
     * trilha (ex.: módulo categoria=extra).
     *
     * @return array{anterior: ?Conteudo, proximo: ?Conteudo}
     */
    public function vizinhosDoConteudo(Conteudo $conteudo): array
    {
        $secao = $conteudo->modulo->secao;

        $sequencia = $this->consultaSequencia()
            ->when(
                $secao === null,
                fn (Builder $q) => $q->whereNull('modulos.secao'),
                fn (Builder $q) => $q->where('modulos.secao', $secao),
            )
            ->get();

        $indice = $sequencia->search(fn (Conteudo $item) => $item->id === $conteudo->id);

        if ($indice === false) {
            return ['anterior' => null, 'proximo' => null];
        }

        return [
            'anterior' => $indice > 0 ? $sequencia[$indice - 1] : null,
            'proximo' => $indice < $sequencia->count() - 1 ? $sequencia[$indice + 1] : null,
        ];
    }

    /**
     * Vitrine do aluno: módulos agrupados por seção (carrosséis). Sem seção
     * explícita, módulos de nível caem em "Aulas" e extras em "Atividades
     * Extras". Ordem das seções: Aulas, seções customizadas (na ordem em que
     * aparecem), Atividades Extras. Dentro da seção: por nível, depois ordem.
     *
     * `bloqueio` de cada card: null (liberado), int (dias até o primeiro
     * conteúdo liberar) ou 'bloqueado' (tudo travado manualmente).
     *
     * @return array<int, array{titulo: string, cards: array<int, array{modulo: Modulo, numero: string, total: int, concluidas: int, bloqueio: int|string|null}>}>
     */
    public function vitrinePara(User $aluno): array
    {
        $modulos = $this->modulos()->with('conteudos')->get();
        $dias = (new Conteudo)->diasDesdeMatricula($aluno, $this);
        $concluidosIds = AlunoProgresso::where('aluno_id', $aluno->id)->pluck('conteudo_id')->all();

        $porSecao = $modulos
            ->sortBy([['nivel', 'asc'], ['ordem', 'asc']])
            ->groupBy(fn (Modulo $modulo) => $modulo->secao ?? ($modulo->categoria === 'extra' ? 'Atividades Extras' : 'Aulas'));

        $peso = fn (string $titulo) => match ($titulo) {
            'Aulas' => 0,
            'Atividades Extras' => 2,
            default => 1,
        };

        return $porSecao
            ->map(fn (SupportCollection $grupo, string $titulo) => [
                'titulo' => $titulo,
                'cards' => $grupo->values()->map(function (Modulo $modulo, int $i) use ($aluno, $dias, $concluidosIds) {
                    $conteudos = $modulo->conteudos;
                    $bloqueio = null;

                    if ($conteudos->isNotEmpty() && ! $conteudos->contains(fn (Conteudo $c) => $c->disponivelPara($aluno, $this, $dias))) {
                        $pendentes = $conteudos->where('bloqueado', false)
                            ->map(fn (Conteudo $c) => $c->diasRestantesPara($aluno, $this, $dias));
                        $bloqueio = $pendentes->isEmpty() ? 'bloqueado' : $pendentes->min();
                    }

                    return [
                        'modulo' => $modulo,
                        'numero' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                        'total' => $conteudos->count(),
                        'concluidas' => $conteudos->whereIn('id', $concluidosIds)->count(),
                        'bloqueio' => $bloqueio,
                    ];
                })->all(),
            ])
            ->sortBy(fn (array $secao) => $peso($secao['titulo']))
            ->values()
            ->all();
    }

    /**
     * Progresso de cada aluno matriculado nas aulas de nível da turma, do
     * menos ativo pro mais ativo (quem nunca concluiu nada vem primeiro).
     *
     * @return SupportCollection<int, array{aluno: User, concluidas: int, total: int, percentual: float, ultima_atividade: ?Carbon}>
     */
    public function progressoDosAlunos(): SupportCollection
    {
        $total = $this->totalAulas();
        $alunos = $this->alunos()->orderBy('name')->get();

        $progresso = AlunoProgresso::query()
            ->selectRaw('aluno_id, count(*) as concluidas, max(concluido_em) as ultima')
            ->whereIn('aluno_id', $alunos->pluck('id'))
            ->whereHas('conteudo.modulo', fn ($q) => $q->where('turma_id', $this->id)->where('categoria', 'nivel'))
            ->groupBy('aluno_id')
            ->get()
            ->keyBy('aluno_id');

        return $alunos
            ->map(function (User $aluno) use ($progresso, $total) {
                $linha = $progresso->get($aluno->id);
                $concluidas = (int) ($linha->concluidas ?? 0);

                return [
                    'aluno' => $aluno,
                    'concluidas' => $concluidas,
                    'total' => $total,
                    'percentual' => $total === 0 ? 0.0 : round($concluidas / $total * 100, 1),
                    'ultima_atividade' => $linha?->ultima ? Carbon::parse($linha->ultima) : null,
                ];
            })
            ->sortBy(fn (array $l) => $l['ultima_atividade']?->timestamp ?? 0)
            ->values();
    }
}
