<?php

namespace App\Livewire\Academico\Aluno;

use App\Models\AlunoProgresso;
use App\Models\AlunoRespostaOpcao;
use App\Models\Conteudo;
use App\Models\ExercicioOpcao;
use App\Models\Turma;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class Aula extends Component
{
    public Turma $turma;

    public Conteudo $conteudo;

    /**
     * @var array<int, array<int>> [pergunta_id => [opcao_id, ...]]
     */
    public array $respostasSelecionadas = [];

    public function mount(Turma $turma, Conteudo $conteudo): void
    {
        abort_unless($conteudo->modulo->turma_id === $turma->id, 404);

        Gate::authorize('view', $conteudo);

        $this->turma = $turma;
        $this->conteudo = $conteudo;
    }

    private function autorizarDisponibilidade(): void
    {
        abort_unless($this->conteudo->disponivelPara(Auth::user(), $this->turma), 403);
    }

    public function toggleConclusao(): void
    {
        $this->autorizarDisponibilidade();

        $aluno = Auth::user();

        if ($this->conteudo->concluidoPor($aluno)) {
            AlunoProgresso::where('aluno_id', $aluno->id)->where('conteudo_id', $this->conteudo->id)->delete();
        } else {
            AlunoProgresso::create([
                'aluno_id' => $aluno->id,
                'conteudo_id' => $this->conteudo->id,
                'concluido_em' => now(),
            ]);
        }
    }

    public function refazerQuiz(): void
    {
        AlunoProgresso::where('aluno_id', Auth::id())->where('conteudo_id', $this->conteudo->id)->delete();
    }

    public function enviarQuiz(): void
    {
        $this->autorizarDisponibilidade();

        $aluno = Auth::user();

        $perguntaIds = $this->conteudo->perguntas()->pluck('id');
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
            ['aluno_id' => $aluno->id, 'conteudo_id' => $this->conteudo->id],
            ['concluido_em' => now()],
        );
    }

    public function render()
    {
        $aluno = Auth::user();
        $conteudo = $this->conteudo;
        $ehQuiz = $conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo === 'quiz';
        $concluido = $conteudo->concluidoPor($aluno);

        return view('livewire.academico.aluno.aula', [
            'modulo' => $conteudo->modulo,
            'disponivel' => $conteudo->disponivelPara($aluno, $this->turma),
            'diasRestantes' => $conteudo->diasRestantesPara($aluno, $this->turma),
            'concluido' => $concluido,
            'ehQuiz' => $ehQuiz,
            'score' => $ehQuiz && $concluido ? $conteudo->corrigirRespostas($aluno) : null,
            'vizinhos' => $this->turma->vizinhosDoConteudo($conteudo),
        ]);
    }
}
