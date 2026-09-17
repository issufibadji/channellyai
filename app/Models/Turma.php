<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * @return array{status: 'proximo'|'tudo-concluido'|'sem-disponivel', url: string}
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
                            'url' => route('academico.minha-turma.modulo', [$this, $modulo]),
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
}
