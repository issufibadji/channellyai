<?php

namespace App\Livewire\Academico;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AlunoManager extends Component
{
    public function render()
    {
        $professor = Auth::user();

        $alunos = User::whereHas('turmasMatriculadas', function ($query) use ($professor) {
            $query->where('professor_id', $professor->id);
        })->with(['turmasMatriculadas' => function ($query) use ($professor) {
            $query->where('professor_id', $professor->id);
        }])->orderBy('name')->get();

        return view('livewire.academico.aluno-manager', [
            'alunos' => $alunos,
        ]);
    }
}
