<?php

namespace App\Livewire\Academico;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.master')]
class TurmaManager extends Component
{
    public ?int $turmaId = null;

    #[Validate('required|exists:cursos,id')]
    public ?int $cursoId = null;

    #[Validate('required|exists:users,id')]
    public ?int $professorId = null;

    #[Validate('required|string|max:255')]
    public string $nome = '';

    #[Validate('nullable|date')]
    public string $dataInicio = '';

    #[Validate('nullable|date')]
    public string $dataFim = '';

    public bool $ativo = true;

    public function create(): void
    {
        $this->reset(['turmaId', 'cursoId', 'professorId', 'nome', 'dataInicio', 'dataFim']);
        $this->ativo = true;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'turma-form');
    }

    public function edit(int $id): void
    {
        $turma = Turma::findOrFail($id);

        $this->turmaId = $turma->id;
        $this->cursoId = $turma->curso_id;
        $this->professorId = $turma->professor_id;
        $this->nome = $turma->nome;
        $this->dataInicio = $turma->data_inicio?->format('Y-m-d') ?? '';
        $this->dataFim = $turma->data_fim?->format('Y-m-d') ?? '';
        $this->ativo = $turma->ativo;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'turma-form');
    }

    public function save(): void
    {
        $this->validate();

        Turma::updateOrCreate(
            ['id' => $this->turmaId],
            [
                'curso_id' => $this->cursoId,
                'professor_id' => $this->professorId,
                'nome' => $this->nome,
                'data_inicio' => $this->dataInicio ?: null,
                'data_fim' => $this->dataFim ?: null,
                'ativo' => $this->ativo,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Turma salva com sucesso.');
    }

    public function delete(int $id): void
    {
        Turma::findOrFail($id)->delete();

        session()->flash('success', 'Turma removida.');
    }

    public function render()
    {
        return view('livewire.academico.turma-manager', [
            'turmas' => Turma::with(['curso', 'professor'])->withCount('alunos')->orderBy('nome')->get(),
            'cursos' => Curso::orderBy('nome')->get(),
            'professores' => User::role('professor')->orderBy('name')->get(),
        ]);
    }
}
