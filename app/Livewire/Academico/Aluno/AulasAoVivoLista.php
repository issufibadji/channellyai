<?php

namespace App\Livewire\Academico\Aluno;

use App\Models\AulaAoVivo;
use App\Models\Turma;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AulasAoVivoLista extends Component
{
    public function render()
    {
        $turmaIds = Turma::doAluno(Auth::user())->pluck('id');

        return view('livewire.academico.aluno.aulas-ao-vivo-lista', [
            'proximas' => AulaAoVivo::with('turma')->ativas()->whereIn('turma_id', $turmaIds)
                ->orderByRaw("case status when 'ao_vivo' then 0 else 1 end")->orderBy('inicio_em')->get(),
            'anteriores' => AulaAoVivo::with(['turma', 'conteudo'])->where('status', AulaAoVivo::ENCERRADA)
                ->whereIn('turma_id', $turmaIds)->latest('inicio_em')->limit(10)->get(),
        ]);
    }
}
