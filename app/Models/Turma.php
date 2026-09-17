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
}
