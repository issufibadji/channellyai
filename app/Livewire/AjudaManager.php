<?php

namespace App\Livewire;

use App\Models\Ajuda;
use App\Models\AjudaFaq;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AjudaManager extends Component
{
    public ?int $ajudaId = null;

    public string $ajudaRole = '';

    public string $ajudaConteudo = '';

    public ?int $faqId = null;

    public string $icone = '';

    public string $pergunta = '';

    public string $resposta = '';

    public int $ordem = 0;

    public bool $ativo = true;

    public function editAjuda(int $id): void
    {
        $ajuda = Ajuda::findOrFail($id);

        $this->ajudaId = $ajuda->id;
        $this->ajudaRole = $ajuda->role;
        $this->ajudaConteudo = $ajuda->conteudo;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'ajuda-form');
    }

    public function saveAjuda(): void
    {
        $this->validate([
            'ajudaConteudo' => 'required|string',
        ]);

        $ajuda = Ajuda::findOrFail($this->ajudaId);
        $ajuda->update(['conteudo' => $this->ajudaConteudo]);

        $this->dispatch('close-modal');
        session()->flash('success', 'Conteúdo de ajuda atualizado com sucesso.');
    }

    public function createFaq(): void
    {
        $this->reset(['faqId', 'icone', 'pergunta', 'resposta', 'ordem', 'ativo']);
        $this->ativo = true;
        $this->ordem = AjudaFaq::paraRole('aluno')->max('ordem') + 1;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'faq-form');
    }

    public function editFaq(int $id): void
    {
        $faq = AjudaFaq::findOrFail($id);

        $this->faqId = $faq->id;
        $this->icone = $faq->icone ?? '';
        $this->pergunta = $faq->pergunta;
        $this->resposta = $faq->resposta;
        $this->ordem = $faq->ordem;
        $this->ativo = $faq->ativo;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'faq-form');
    }

    public function saveFaq(): void
    {
        $this->validate([
            'icone' => 'nullable|string|max:255',
            'pergunta' => 'required|string|max:255',
            'resposta' => 'required|string',
            'ordem' => 'integer',
            'ativo' => 'boolean',
        ]);

        $faq = $this->faqId ? AjudaFaq::findOrFail($this->faqId) : new AjudaFaq(['role' => 'aluno']);

        $faq->icone = $this->icone ?: null;
        $faq->pergunta = $this->pergunta;
        $faq->resposta = $this->resposta;
        $faq->ordem = $this->ordem;
        $faq->ativo = $this->ativo;
        $faq->save();

        $this->dispatch('close-modal');
        session()->flash('success', 'Pergunta salva com sucesso.');
    }

    public function deleteFaq(int $id): void
    {
        AjudaFaq::findOrFail($id)->delete();

        session()->flash('success', 'Pergunta removida.');
    }

    public function toggleAtivo(int $id): void
    {
        $faq = AjudaFaq::findOrFail($id);
        $faq->update(['ativo' => ! $faq->ativo]);
    }

    public function moveUp(int $id): void
    {
        $this->swapOrdem($id, 'up');
    }

    public function moveDown(int $id): void
    {
        $this->swapOrdem($id, 'down');
    }

    private function swapOrdem(int $id, string $direction): void
    {
        $faq = AjudaFaq::findOrFail($id);

        $vizinho = AjudaFaq::paraRole('aluno')
            ->where('ordem', $direction === 'up' ? '<' : '>', $faq->ordem)
            ->orderBy('ordem', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if (! $vizinho) {
            return;
        }

        [$ordemFaq, $ordemVizinho] = [$faq->ordem, $vizinho->ordem];

        $faq->update(['ordem' => $ordemVizinho]);
        $vizinho->update(['ordem' => $ordemFaq]);
    }

    public function render()
    {
        return view('livewire.admin.ajuda-manager', [
            'ajudas' => Ajuda::orderBy('role')->get(),
            'faqs' => AjudaFaq::paraRole('aluno')->get(),
        ]);
    }
}
