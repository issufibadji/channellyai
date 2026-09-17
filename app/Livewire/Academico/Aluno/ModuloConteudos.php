<?php

namespace App\Livewire\Academico\Aluno;

use App\Models\AlunoProgresso;
use App\Models\Conteudo;
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

    public function render()
    {
        $aluno = Auth::user();
        $conteudos = $this->modulo->conteudos;

        $diasDesdeMatricula = null;
        $itens = $conteudos->map(function ($conteudo) use ($aluno, &$diasDesdeMatricula) {
            $diasDesdeMatricula ??= $conteudo->diasDesdeMatricula($aluno, $this->turma);

            return [
                'conteudo' => $conteudo,
                'disponivel' => $conteudo->disponivelPara($aluno, $this->turma, $diasDesdeMatricula),
                'diasRestantes' => $conteudo->diasRestantesPara($aluno, $this->turma, $diasDesdeMatricula),
                'concluido' => $conteudo->concluidoPor($aluno),
            ];
        });

        return view('livewire.academico.aluno.modulo-conteudos', [
            'itens' => $itens,
        ]);
    }
}
