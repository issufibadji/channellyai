<?php

namespace App\Livewire\Atendimento;

use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\ChatbotRegra;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class ChatbotManager extends Component
{
    public ?int $regraId = null;

    public string $gatilho = '';

    public string $resposta = '';

    public string $setorTransferencia = '';

    public bool $ativo = true;

    public int $order = 0;

    public function create(): void
    {
        $this->reset(['regraId', 'gatilho', 'resposta', 'setorTransferencia', 'order']);
        $this->ativo = true;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'regra-form');
    }

    public function edit(int $id): void
    {
        $regra = ChatbotRegra::findOrFail($id);

        $this->regraId = $regra->id;
        $this->gatilho = $regra->gatilho;
        $this->resposta = $regra->resposta ?? '';
        $this->setorTransferencia = $regra->setor_transferencia ?? '';
        $this->ativo = $regra->ativo;
        $this->order = $regra->order;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'regra-form');
    }

    public function save(): void
    {
        $this->validate([
            'gatilho' => 'required|string|max:255',
            'resposta' => 'nullable|string|max:1000',
            'setorTransferencia' => 'nullable|string|in:'.implode(',', array_keys(Atendimento::SETORES)),
            'order' => 'integer|min:0',
        ]);

        ChatbotRegra::updateOrCreate(
            ['id' => $this->regraId],
            [
                'gatilho' => $this->gatilho,
                'resposta' => $this->resposta ?: null,
                'setor_transferencia' => $this->setorTransferencia ?: null,
                'ativo' => $this->ativo,
                'order' => $this->order,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Regra salva com sucesso.');
    }

    public function toggleAtivo(int $id): void
    {
        $regra = ChatbotRegra::findOrFail($id);
        $regra->update(['ativo' => ! $regra->ativo]);
    }

    public function delete(int $id): void
    {
        ChatbotRegra::findOrFail($id)->delete();

        session()->flash('success', 'Regra removida.');
    }

    public function render()
    {
        return view('livewire.atendimento.chatbot-manager', [
            'regras' => ChatbotRegra::orderBy('order')->orderBy('gatilho')->get(),
        ]);
    }
}
