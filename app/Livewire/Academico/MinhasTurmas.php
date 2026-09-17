<?php

namespace App\Livewire\Academico;

use App\Models\Turma;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class MinhasTurmas extends Component
{
    public function render()
    {
        return view('livewire.academico.minhas-turmas', [
            'turmas' => Turma::doProfessor(Auth::user())
                ->with('curso', 'modulos')
                ->withCount('alunos')
                ->orderBy('nome')
                ->get(),
        ]);
    }
}
