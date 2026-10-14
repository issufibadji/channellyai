<?php

namespace App\Livewire\Atendimento;

use App\Models\Agente;
use App\Services\CurrentEstabelecimento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AgenteManager extends Component
{
    public string $nomeNovoAgente = '';

    public function mount(): void
    {
        if (! app(CurrentEstabelecimento::class)->id()) {
            session()->flash('warning', 'Selecione um estabelecimento no topo da página antes de gerenciar agentes.');
            $this->redirect(route('atendimento.dashboard'), navigate: false);
        }
    }

    public function criar(): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $this->validate(['nomeNovoAgente' => 'required|string|max:255']);

        $agente = Agente::create([
            'estabelecimento_id' => app(CurrentEstabelecimento::class)->id(),
            'nome' => $this->nomeNovoAgente,
        ]);

        $this->redirect(route('atendimento.chatbot.show', $agente), navigate: false);
    }

    public function excluir(int $agenteId): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $agente = Agente::where('estabelecimento_id', app(CurrentEstabelecimento::class)->id())
            ->where('id', $agenteId)
            ->first();

        abort_if(! $agente, 404);

        $agente->delete();

        session()->flash('success', 'Agente excluído.');
    }

    public function render()
    {
        $agentes = Agente::where('estabelecimento_id', app(CurrentEstabelecimento::class)->id())
            ->with('dadosNegocio')
            ->orderBy('nome')
            ->get();

        return view('livewire.atendimento.agente-manager', ['agentes' => $agentes]);
    }
}
