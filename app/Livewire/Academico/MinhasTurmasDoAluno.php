<?php

namespace App\Livewire\Academico;

use App\Models\Turma;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class MinhasTurmasDoAluno extends Component
{
    public function render()
    {
        return view('livewire.academico.minhas-turmas-do-aluno', [
            'turmas' => Turma::doAluno(Auth::user())
                ->with('curso', 'professor', 'modulos.conteudos')
                ->orderBy('nome')
                ->get(),
        ]);
    }
}
