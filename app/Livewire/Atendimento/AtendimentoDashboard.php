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
        return view('livewire.atendimento.atendimento-dashboard', [
            'total' => Atendimento::count(),
            'abertos' => Atendimento::where('status', 'aberto')->count(),
            'emAtendimento' => Atendimento::where('status', 'em_atendimento')->count(),
            'resolvidos' => Atendimento::where('status', 'resolvido')->count(),
            'satisfacaoMedia' => round((float) Atendimento::whereNotNull('satisfacao')->avg('satisfacao'), 1),
            'recentes' => Atendimento::with(['cliente', 'canal'])->latest()->limit(6)->get(),
        ]);
    }
}
