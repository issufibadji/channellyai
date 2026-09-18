<?php

namespace App\Livewire\Academico;

use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.master')]
class MatriculaManager extends Component
{
    public Turma $turma;

    #[Validate('required|exists:users,id')]
    public ?int $alunoId = null;

    public function mount(Turma $turma): void
    {
        Gate::authorize('manageMatricula', $turma);

        $this->turma = $turma;
    }

    public function matricular(): void
    {
        $this->validate();

        $this->turma->alunos()->syncWithoutDetaching([
            $this->alunoId => ['data_matricula' => now()->toDateString(), 'status' => 'ativo'],
        ]);

        $this->reset('alunoId');
        session()->flash('success', 'Aluno matriculado com sucesso.');
    }

    public function desmatricular(int $alunoId): void
    {
        $this->turma->alunos()->detach($alunoId);

        session()->flash('success', 'Matrícula removida.');
    }

    public function render()
    {
        $matriculadosIds = $this->turma->alunos()->pluck('users.id');

        return view('livewire.academico.matricula-manager', [
            'alunos' => $this->turma->alunos()->orderBy('name')->get(),
            'candidatos' => User::role('aluno')->whereNotIn('id', $matriculadosIds)->orderBy('name')->get(),
        ]);
    }
}
