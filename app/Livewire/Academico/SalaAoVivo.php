<?php

namespace App\Livewire\Academico;

use App\Models\AulaAoVivo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class SalaAoVivo extends Component
{
    public AulaAoVivo $aula;

    public function mount(AulaAoVivo $aula): void
    {
        // Professor da turma, admin ou aluno matriculado — mesma regra de ver a turma.
        Gate::authorize('view', $aula->turma);

        $this->aula = $aula;
    }

    public function iniciar(): void
    {
        Gate::authorize('manageConteudo', $this->aula->turma);

        $this->aula->iniciar();
        $this->aula->refresh();
    }

    public function encerrar(): void
    {
        Gate::authorize('manageConteudo', $this->aula->turma);

        $this->aula->encerrar();
        $this->aula->refresh();
    }

    public function render()
    {
        $this->aula->refresh();
        $ehProfessor = Gate::allows('manageConteudo', $this->aula->turma);

        return view('livewire.academico.sala-ao-vivo', [
            'ehProfessor' => $ehProfessor,
            'urlSala' => $this->aula->estaAoVivo() ? $this->aula->urlSala(Auth::user(), $ehProfessor) : null,
        ]);
    }
}
