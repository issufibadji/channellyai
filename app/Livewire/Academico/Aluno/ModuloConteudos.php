<?php

namespace App\Livewire\Academico\Aluno;

use App\Models\AlunoProgresso;
use App\Models\AlunoRespostaOpcao;
use App\Models\Conteudo;
use App\Models\ExercicioOpcao;
use App\Models\Modulo;
use App\Models\Turma;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class ModuloConteudos extends Component
{
    public Turma $turma;

    public Modulo $modulo;

    /**
     * @var array<int, array<int>> [pergunta_id => [opcao_id, ...]]
     */
    public array $respostasSelecionadas = [];

    public function mount(Turma $turma, Modulo $modulo): void
    {
        Gate::authorize('view', $modulo);

        $this->turma = $turma;
        $this->modulo = $modulo;
    }

    public function toggleConclusao(int $conteudoId): void
    {
        $aluno = Auth::user();
        $conteudo = Conteudo::findOrFail($conteudoId);

        abort_unless($conteudo->disponivelPara($aluno, $this->turma), 403);

        if ($conteudo->concluidoPor($aluno)) {
            AlunoProgresso::where('aluno_id', $aluno->id)->where('conteudo_id', $conteudoId)->delete();
        } else {
            AlunoProgresso::create([
                'aluno_id' => $aluno->id,
                'conteudo_id' => $conteudoId,
                'concluido_em' => now(),
            ]);
        }
    }

    public function refazerQuiz(int $conteudoId): void
    {
        AlunoProgresso::where('aluno_id', Auth::id())->where('conteudo_id', $conteudoId)->delete();
    }

    public function enviarQuiz(int $conteudoId): void
    {
        $aluno = Auth::user();
        $conteudo = Conteudo::findOrFail($conteudoId);

        abort_unless($conteudo->disponivelPara($aluno, $this->turma), 403);

        $perguntaIds = $conteudo->perguntas()->pluck('id');
        $opcaoIdsDoConteudo = ExercicioOpcao::whereIn('pergunta_id', $perguntaIds)->pluck('id');

        AlunoRespostaOpcao::where('aluno_id', $aluno->id)
            ->whereIn('opcao_id', $opcaoIdsDoConteudo)
            ->delete();

        foreach ($perguntaIds as $perguntaId) {
            foreach ($this->respostasSelecionadas[$perguntaId] ?? [] as $opcaoId) {
                AlunoRespostaOpcao::create([
                    'aluno_id' => $aluno->id,
                    'opcao_id' => (int) $opcaoId,
                ]);
            }
        }

        AlunoProgresso::updateOrCreate(
            ['aluno_id' => $aluno->id, 'conteudo_id' => $conteudoId],
            ['concluido_em' => now()],
        );
    }

    public function render()
    {
        $aluno = Auth::user();
        $conteudos = $this->modulo->conteudos;

        $diasDesdeMatricula = null;
        $itens = $conteudos->map(function ($conteudo) use ($aluno, &$diasDesdeMatricula) {
            $diasDesdeMatricula ??= $conteudo->diasDesdeMatricula($aluno, $this->turma);
            $concluido = $conteudo->concluidoPor($aluno);
            $ehQuiz = $conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo === 'quiz';

            return [
                'conteudo' => $conteudo,
                'disponivel' => $conteudo->disponivelPara($aluno, $this->turma, $diasDesdeMatricula),
                'diasRestantes' => $conteudo->diasRestantesPara($aluno, $this->turma, $diasDesdeMatricula),
                'concluido' => $concluido,
                'vizinhos' => $this->turma->vizinhosDoConteudo($conteudo),
                'score' => $ehQuiz && $concluido ? $conteudo->corrigirRespostas($aluno) : null,
            ];
        });

        return view('livewire.academico.aluno.modulo-conteudos', [
            'itens' => $itens,
        ]);
    }
}
