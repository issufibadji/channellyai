<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Conteudo extends Model
{
    use HasFactory;

    protected $fillable = [
        'modulo_id', 'titulo', 'tipo', 'corpo', 'arquivo_path', 'url_externa',
        'ordem', 'dias_liberacao', 'bloqueado',
    ];

    protected function casts(): array
    {
        return [
            'bloqueado' => 'boolean',
        ];
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    public function progressos(): HasMany
    {
        return $this->hasMany(AlunoProgresso::class);
    }

    /**
     * Se esse aluno já concluiu esse conteúdo.
     */
    public function concluidoPor(User $aluno): bool
    {
        return $this->progressos()->where('aluno_id', $aluno->id)->exists();
    }

    /**
     * Se o conteúdo está liberado pra esse aluno, nessa turma, agora.
     * Passe $diasDesdeMatricula quando for checar vários conteúdos do
     * mesmo aluno/turma numa mesma request, pra evitar N+1.
     */
    public function disponivelPara(User $aluno, Turma $turma, ?int $diasDesdeMatricula = null): bool
    {
        if ($this->bloqueado) {
            return false;
        }

        $dias = $diasDesdeMatricula ?? $this->diasDesdeMatricula($aluno, $turma);

        return $dias >= $this->dias_liberacao;
    }

    /**
     * Quantos dias faltam pra esse conteúdo liberar (0 se já liberado).
     */
    public function diasRestantesPara(User $aluno, Turma $turma, ?int $diasDesdeMatricula = null): int
    {
        $dias = $diasDesdeMatricula ?? $this->diasDesdeMatricula($aluno, $turma);

        return max(0, $this->dias_liberacao - $dias);
    }

    /**
     * Dias corridos desde a matrícula do aluno nessa turma. Não depende
     * do conteúdo em si — calcule uma vez por aluno/turma e reaproveite
     * entre várias chamadas de disponivelPara()/diasRestantesPara().
     */
    public function diasDesdeMatricula(User $aluno, Turma $turma): int
    {
        $pivotDate = $turma->alunos()->where('users.id', $aluno->id)->first()?->pivot->data_matricula;

        return $pivotDate ? Carbon::parse($pivotDate)->diffInDays(now()) : 0;
    }
}
