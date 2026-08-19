<?php

namespace App\Livewire\Atendimento;

use App\Models\Atendimento\Atendimento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AtendimentoDashboard extends Component
{
    public function render()
    {
        $trend = collect(range(13, 0))->map(function (int $daysAgo) {
            $date = now()->subDays($daysAgo);

            return [
                'label' => $date->format('d/m'),
                'count' => Atendimento::whereDate('created_at', $date->format('Y-m-d'))->count(),
            ];
        });

        $statusCounts = Atendimento::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $canalCounts = Atendimento::query()
            ->join('canais', 'canais.id', '=', 'atendimentos.canal_id')
            ->selectRaw('canais.nome as canal, count(*) as total')
            ->groupBy('canais.nome')
            ->orderByDesc('total')
            ->pluck('total', 'canal');

        $ultimoAtendimento = Atendimento::with(['cliente', 'canal'])->latest()->first();
        $ultimasMensagens = $ultimoAtendimento
            ? $ultimoAtendimento->mensagens()->latest()->limit(4)->get()->reverse()
            : collect();

        return view('livewire.atendimento.atendimento-dashboard', [
            'total' => Atendimento::count(),
            'abertos' => Atendimento::where('status', 'aberto')->count(),
            'emAtendimento' => Atendimento::where('status', 'em_atendimento')->count(),
            'resolvidos' => Atendimento::where('status', 'resolvido')->count(),
            'satisfacaoMedia' => round((float) Atendimento::whereNotNull('satisfacao')->avg('satisfacao'), 1),
            'recentes' => Atendimento::with(['cliente', 'canal'])->latest()->limit(6)->get(),
            'trend' => $trend,
            'statusCounts' => $statusCounts,
            'canalCounts' => $canalCounts,
            'ultimoAtendimento' => $ultimoAtendimento,
            'ultimasMensagens' => $ultimasMensagens,
        ]);
    }
}
