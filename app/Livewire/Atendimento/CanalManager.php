<?php

namespace App\Livewire\Atendimento;

use App\Models\Atendimento\Canal;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class CanalManager extends Component
{
    public ?int $canalId = null;

    public string $nome = '';

    public string $tipo = 'whatsapp';

    public bool $ativo = true;

    public function create(): void
    {
        $this->reset(['canalId', 'nome', 'tipo', 'ativo']);
        $this->tipo = 'whatsapp';
        $this->ativo = true;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'canal-form');
    }

    public function edit(int $id): void
    {
        $canal = Canal::findOrFail($id);

        $this->canalId = $canal->id;
        $this->nome = $canal->nome;
        $this->tipo = $canal->tipo;
        $this->ativo = $canal->ativo;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'canal-form');
    }

    public function save(): void
    {
        $this->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|string|in:'.implode(',', array_keys(Canal::TIPOS)),
            'ativo' => 'boolean',
        ]);

        Canal::updateOrCreate(
            ['id' => $this->canalId],
            ['nome' => $this->nome, 'tipo' => $this->tipo, 'ativo' => $this->ativo],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Canal salvo com sucesso.');
    }

    public function toggleAtivo(int $id): void
    {
        $canal = Canal::findOrFail($id);
        $canal->update(['ativo' => ! $canal->ativo]);
    }

    public function delete(int $id): void
    {
        Canal::findOrFail($id)->delete();

        session()->flash('success', 'Canal removido.');
    }

    public function render()
    {
        return view('livewire.atendimento.canal-manager', [
            'canais' => Canal::orderBy('nome')->get(),
        ]);
    }
}
