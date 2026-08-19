<?php

namespace App\Livewire\Atendimento;

use App\Models\Atendimento\Cliente;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.master')]
class ClienteManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $clienteId = null;

    public string $nome = '';

    public string $email = '';

    public string $telefone = '';

    public string $documento = '';

    public string $notas = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['clienteId', 'nome', 'email', 'telefone', 'documento', 'notas']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'cliente-form');
    }

    public function edit(int $id): void
    {
        $cliente = Cliente::findOrFail($id);

        $this->clienteId = $cliente->id;
        $this->nome = $cliente->nome;
        $this->email = $cliente->email ?? '';
        $this->telefone = $cliente->telefone ?? '';
        $this->documento = $cliente->documento ?? '';
        $this->notas = $cliente->notas ?? '';

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'cliente-form');
    }

    public function save(): void
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:30',
            'documento' => 'nullable|string|max:30',
            'notas' => 'nullable|string|max:1000',
        ]);

        Cliente::updateOrCreate(
            ['id' => $this->clienteId],
            [
                'nome' => $this->nome,
                'email' => $this->email ?: null,
                'telefone' => $this->telefone ?: null,
                'documento' => $this->documento ?: null,
                'notas' => $this->notas ?: null,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Cliente salvo com sucesso.');
    }

    public function delete(int $id): void
    {
        Cliente::findOrFail($id)->delete();

        session()->flash('success', 'Cliente removido.');
    }

    public function render()
    {
        $clientes = Cliente::query()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('nome', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('telefone', 'like', "%{$this->search}%");
            }))
            ->orderBy('nome')
            ->paginate(15);

        return view('livewire.atendimento.cliente-manager', ['clientes' => $clientes]);
    }
}
