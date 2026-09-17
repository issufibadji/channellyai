<?php

namespace App\Livewire\Academico\Aluno;

use App\Models\Turma;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class MinhasTurmasLista extends Component
{
    public function render()
    {
        $aluno = Auth::user();

        $turmaRecente = $aluno->turmasMatriculadas()
            ->orderByDesc('turma_aluno.data_matricula')
            ->first();

        $continuar = $turmaRecente
            ? $turmaRecente->proximoConteudoDisponivelPara($aluno)
            : ['status' => 'sem-turma', 'url' => null];

        return view('livewire.academico.aluno.minhas-turmas-lista', [
            'turmas' => Turma::doAluno($aluno)->with('curso', 'professor')->orderBy('nome')->get(),
            'continuar' => $continuar,
        ]);
    }
}
