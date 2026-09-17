<?php

namespace App\Livewire\Academico;

use App\Models\Curso;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.master')]
class CursoManager extends Component
{
    public ?int $cursoId = null;

    #[Validate('required|string|max:255')]
    public string $nome = '';

    #[Validate('required|in:longo_prazo,curto_prazo')]
    public string $tipo = 'longo_prazo';

    #[Validate('nullable|string')]
    public string $descricao = '';

    public bool $ativo = true;

    public function create(): void
    {
        $this->reset(['cursoId', 'nome', 'tipo', 'descricao']);
        $this->ativo = true;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'curso-form');
    }

    public function edit(int $id): void
    {
        $curso = Curso::findOrFail($id);

        $this->cursoId = $curso->id;
        $this->nome = $curso->nome;
        $this->tipo = $curso->tipo;
        $this->descricao = $curso->descricao ?? '';
        $this->ativo = $curso->ativo;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'curso-form');
    }

    public function save(): void
    {
        $this->validate();

        Curso::updateOrCreate(
            ['id' => $this->cursoId],
            [
                'nome' => $this->nome,
                'tipo' => $this->tipo,
                'descricao' => $this->descricao ?: null,
                'ativo' => $this->ativo,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Curso salvo com sucesso.');
    }

    public function delete(int $id): void
    {
        Curso::findOrFail($id)->delete();

        session()->flash('success', 'Curso removido.');
    }

    public function render()
    {
        return view('livewire.academico.curso-manager', [
            'cursos' => Curso::withCount('turmas')->orderBy('nome')->get(),
        ]);
    }
}
