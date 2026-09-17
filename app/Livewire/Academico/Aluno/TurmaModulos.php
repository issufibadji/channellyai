<?php

namespace App\Livewire\Academico\Aluno;

use App\Models\Turma;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class TurmaModulos extends Component
{
    private const NIVEIS_POR_ABA = [
        'basico' => ['A1', 'A2'],
        'intermediario' => ['B1', 'B2'],
        'avancado' => ['C1', 'C2'],
    ];

    public Turma $turma;

    public string $abaAtiva = 'basico';

    public function mount(Turma $turma): void
    {
        Gate::authorize('view', $turma);

        $this->turma = $turma;
    }

    public function selecionarAba(string $aba): void
    {
        if (array_key_exists($aba, self::NIVEIS_POR_ABA)) {
            $this->abaAtiva = $aba;
        }
    }

    public function render()
    {
        $modulosNivel = $this->turma->modulos()
            ->where('categoria', 'nivel')
            ->whereIn('nivel', self::NIVEIS_POR_ABA[$this->abaAtiva])
            ->withCount('conteudos')
            ->get();

        $modulosExtra = $this->turma->modulos()
            ->where('categoria', 'extra')
            ->withCount('conteudos')
            ->get();

        return view('livewire.academico.aluno.turma-modulos', [
            'abas' => array_keys(self::NIVEIS_POR_ABA),
            'modulosNivel' => $modulosNivel,
            'modulosExtra' => $modulosExtra,
        ]);
    }
}
