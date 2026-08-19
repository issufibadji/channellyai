<?php

namespace App\Livewire\Atendimento;

use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.master')]
class AtendimentoManager extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $canalFilter = '';

    public ?int $clienteId = null;

    public ?int $canalId = null;

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCanalFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['clienteId', 'canalId']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'atendimento-form');
    }

    public function save(): void
    {
        $this->validate([
            'clienteId' => 'required|exists:clientes,id',
            'canalId' => 'required|exists:canais,id',
        ]);

        $atendimento = Atendimento::create([
            'cliente_id' => $this->clienteId,
            'canal_id' => $this->canalId,
            'status' => 'aberto',
        ]);

        $this->dispatch('close-modal');

        $this->redirect(route('atendimento.show', $atendimento), navigate: false);
    }

    public function render()
    {
        $atendimentos = Atendimento::with(['cliente', 'canal', 'assignedTo'])
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->canalFilter, fn ($query) => $query->where('canal_id', $this->canalFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.atendimento.atendimento-manager', [
            'atendimentos' => $atendimentos,
            'clientes' => Cliente::orderBy('nome')->get(),
            'canais' => Canal::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }
}
