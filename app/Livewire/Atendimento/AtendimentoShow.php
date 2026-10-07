<?php

namespace App\Livewire\Atendimento;

use App\Models\Atendimento\Atendimento;
use App\Models\User;
use App\Services\Atendimento\ChatbotEngine;
use App\Services\CurrentEstabelecimento;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AtendimentoShow extends Component
{
    public Atendimento $atendimento;

    public string $mensagem = '';

    public string $remetente = 'cliente';

    public function mount(Atendimento $atendimento): void
    {
        // A resolução do route-model-binding roda antes do middleware "setEstabelecimento"
        // (prioridade fixa do Laravel), então o escopo global pode ter usado um contexto
        // de estabelecimento desatualizado — revalida aqui, já com o contexto correto.
        abort_unless($atendimento->estabelecimento_id === app(CurrentEstabelecimento::class)->id(), 404);

        $this->atendimento = $atendimento;
    }

    public function enviarMensagem(ChatbotEngine $engine): void
    {
        $this->validate([
            'mensagem' => 'required|string|max:2000',
            'remetente' => 'required|in:cliente,atendente',
        ]);

        $this->atendimento->mensagens()->create([
            'remetente' => $this->remetente,
            'autor_id' => $this->remetente === 'atendente' ? Auth::id() : null,
            'conteudo' => $this->mensagem,
        ]);

        if ($this->remetente === 'atendente' && $this->atendimento->status === 'aberto') {
            $this->atendimento->update(['status' => 'em_atendimento', 'assigned_to' => Auth::id()]);
        }

        if ($this->remetente === 'cliente' && $this->atendimento->status !== 'em_atendimento') {
            $resposta = $engine->responder($this->atendimento, $this->mensagem);

            $this->atendimento->mensagens()->create([
                'remetente' => 'ia',
                'conteudo' => $resposta->resposta,
            ]);

            if ($resposta->setorTransferencia) {
                $this->atendimento->update(['setor' => $resposta->setorTransferencia, 'status' => 'aguardando']);
            }
        }

        $this->reset('mensagem');
        $this->atendimento->refresh();
    }

    public function atribuirParaMim(): void
    {
        $this->atendimento->update(['assigned_to' => Auth::id(), 'status' => 'em_atendimento']);
        $this->atendimento->refresh();
    }

    public function atribuirPara(int $userId): void
    {
        $this->atendimento->update(['assigned_to' => $userId]);
        $this->atendimento->refresh();
    }

    public function atualizarStatus(string $status): void
    {
        abort_unless(array_key_exists($status, Atendimento::STATUSES), 422);

        $this->atendimento->update([
            'status' => $status,
            'resolved_at' => $status === 'resolvido' ? now() : null,
        ]);

        $this->atendimento->refresh();
    }

    public function atualizarSetor(string $setor): void
    {
        abort_unless($setor === '' || array_key_exists($setor, Atendimento::SETORES), 422);

        $this->atendimento->update(['setor' => $setor ?: null]);
        $this->atendimento->refresh();
    }

    public function render()
    {
        return view('livewire.atendimento.atendimento-show', [
            'mensagens' => $this->atendimento->mensagens()->with('autor')->get(),
            'atendentes' => User::orderBy('name')->get(),
        ]);
    }
}
