<?php

namespace App\Livewire\Atendimento;

use App\Models\Atendimento\Atendimento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class Relatorios extends Component
{
    public string $dataInicio = '';

    public string $dataFim = '';

    public function mount(): void
    {
        $this->dataInicio = now()->subDays(30)->format('Y-m-d');
        $this->dataFim = now()->format('Y-m-d');
    }

    protected function baseQuery()
    {
        return Atendimento::query()
            ->whereDate('atendimentos.created_at', '>=', $this->dataInicio)
            ->whereDate('atendimentos.created_at', '<=', $this->dataFim);
    }

    public function render()
    {
        $porStatus = (clone $this->baseQuery())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $porCanal = (clone $this->baseQuery())
            ->join('canais', 'canais.id', '=', 'atendimentos.canal_id')
            ->selectRaw('canais.nome as canal, count(*) as total')
            ->groupBy('canais.nome')
            ->pluck('total', 'canal');

        $porSetor = (clone $this->baseQuery())
            ->whereNotNull('setor')
            ->selectRaw('setor, count(*) as total')
            ->groupBy('setor')
            ->pluck('total', 'setor');

        return view('livewire.atendimento.relatorios', [
            'total' => (clone $this->baseQuery())->count(),
            'porStatus' => $porStatus,
            'porCanal' => $porCanal,
            'porSetor' => $porSetor,
        ]);
    }
}
