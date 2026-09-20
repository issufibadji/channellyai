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
        'modulo_id', 'titulo', 'tipo', 'exercicio_subtipo', 'corpo', 'arquivo_path', 'url_externa',
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

    public function perguntas(): HasMany
    {
        return $this->hasMany(ExercicioPergunta::class)->orderBy('ordem');
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

    /**
     * Percentual de acerto do aluno no quiz deste conteúdo. Uma pergunta só
     * conta como certa se o conjunto de opções marcadas for EXATAMENTE igual
     * ao conjunto de opções corretas — nem a mais, nem a menos.
     */
    public function corrigirRespostas(User $aluno): float
    {
        $perguntas = $this->perguntas()->with('opcoes')->get();

        if ($perguntas->isEmpty()) {
            return 0.0;
        }

        $opcaoIds = $perguntas->pluck('opcoes')->flatten()->pluck('id');

        $selecionadas = AlunoRespostaOpcao::where('aluno_id', $aluno->id)
            ->whereIn('opcao_id', $opcaoIds)
            ->pluck('opcao_id');

        $certas = $perguntas->filter(function (ExercicioPergunta $pergunta) use ($selecionadas) {
            $corretas = $pergunta->opcoes->where('correta', true)->pluck('id')->sort()->values();
            $marcadas = $pergunta->opcoes->pluck('id')->intersect($selecionadas)->sort()->values();

            return $corretas->all() === $marcadas->all();
        })->count();

        return round($certas / $perguntas->count() * 100, 1);
    }

    /**
     * Nome do heroicon (outline) que representa o tipo, pra listas e cards.
     */
    public function icone(): string
    {
        return match ($this->tipo) {
            'video', 'video_curto' => 'play-circle',
            'pdf' => 'document-arrow-down',
            'exercicio' => 'clipboard-document-check',
            'link' => 'link',
            default => 'document-text',
        };
    }

    public function rotuloTipo(): string
    {
        return match ($this->tipo) {
            'video' => 'Vídeo',
            'video_curto' => 'Vídeo curto',
            'pdf' => 'PDF',
            'exercicio' => $this->exercicio_subtipo === 'quiz' ? 'Quiz' : 'Exercício',
            'link' => 'Link',
            default => 'Texto',
        };
    }

    /**
     * URL de embed pra vídeo do YouTube/Vimeo a partir de url_externa, ou
     * null se não reconhecer o padrão (a view trata como link simples).
     */
    public function embedUrlVideo(): ?string
    {
        if (! $this->url_externa) {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]+)/', $this->url_externa, $m)) {
            return "https://www.youtube.com/embed/{$m[1]}";
        }

        if (preg_match('/vimeo\.com\/(\d+)/', $this->url_externa, $m)) {
            return "https://player.vimeo.com/video/{$m[1]}";
        }

        // Google Drive (file/d/ID/view, open?id=ID, uc?id=ID) → player de /preview.
        if (preg_match('/drive\.google\.com\/(?:file\/d\/|open\?id=|uc\?id=)([A-Za-z0-9_-]+)/', $this->url_externa, $m)) {
            return "https://drive.google.com/file/d/{$m[1]}/preview";
        }

        return null;
    }
}
