<?php

namespace App\Livewire\Academico;

use App\Models\AulaAoVivo;
use App\Models\Turma;
use App\Notifications\AulaAoVivoAgendada;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AulaAoVivoManager extends Component
{
    public Turma $turma;

    public ?int $aulaId = null;

    public string $titulo = '';

    public string $descricao = '';

    public string $inicioEm = '';

    public int $duracaoMinutos = 60;

    public ?int $gravacaoAulaId = null;

    public string $gravacaoUrl = '';

    /** 'novo' = módulo "Aulas Gravadas" (criado se preciso) ou o id de um módulo da turma. */
    public string $moduloDestino = 'novo';

    public function mount(Turma $turma): void
    {
        Gate::authorize('manageConteudo', $turma);

        $this->turma = $turma;
    }

    public function create(): void
    {
        $this->reset(['aulaId', 'titulo', 'descricao', 'inicioEm', 'duracaoMinutos']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'aula-ao-vivo-form');
    }

    public function edit(int $id): void
    {
        $aula = $this->aula($id);

        $this->aulaId = $aula->id;
        $this->titulo = $aula->titulo;
        $this->descricao = $aula->descricao ?? '';
        $this->inicioEm = $aula->inicio_em->format('Y-m-d\TH:i');
        $this->duracaoMinutos = $aula->duracao_minutos;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'aula-ao-vivo-form');
    }

    public function save(): void
    {
        $this->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:2000',
            'inicioEm' => 'required|date',
            'duracaoMinutos' => 'required|integer|min:15|max:480',
        ]);

        $dados = [
            'titulo' => $this->titulo,
            'descricao' => $this->descricao ?: null,
            'inicio_em' => $this->inicioEm,
            'duracao_minutos' => $this->duracaoMinutos,
        ];

        if ($this->aulaId) {
            $this->aula($this->aulaId)->update($dados);
        } else {
            $aula = $this->turma->aulasAoVivo()->create($dados + ['sala' => AulaAoVivo::gerarSala()]);

            Notification::send($this->turma->alunos, new AulaAoVivoAgendada($aula->load('turma')));
        }

        $this->dispatch('close-modal');
        session()->flash('success', 'Aula ao vivo salva com sucesso.');
    }

    public function abrirGravacao(int $id): void
    {
        $aula = $this->aula($id);

        $this->gravacaoAulaId = $aula->id;
        $this->gravacaoUrl = $aula->conteudo?->url_externa ?? '';
        $this->moduloDestino = $aula->conteudo ? (string) $aula->conteudo->modulo_id : 'novo';

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'gravacao-form');
    }

    public function salvarGravacao(): void
    {
        $aula = $this->aula((int) $this->gravacaoAulaId);

        if (! $aula->estaEncerrada()) {
            $this->addError('gravacaoUrl', 'Só dá pra publicar a gravação depois de encerrar a aula.');

            return;
        }

        $this->validate([
            'gravacaoUrl' => 'required|url|max:2000',
            'moduloDestino' => 'required',
        ]);

        // O módulo escolhido tem que ser da própria turma.
        $modulo = $this->moduloDestino === 'novo'
            ? null
            : $this->turma->modulos()->findOrFail((int) $this->moduloDestino);

        $aula->publicarGravacao($this->gravacaoUrl, $modulo);

        $this->dispatch('close-modal');
        session()->flash('success', 'Gravação publicada como vídeo da turma.');
    }

    public function iniciar(int $id): void
    {
        $this->aula($id)->iniciar();

        session()->flash('success', 'Aula iniciada — os alunos já podem entrar.');
    }

    public function encerrar(int $id): void
    {
        $this->aula($id)->encerrar();

        session()->flash('success', 'Aula encerrada.');
    }

    public function delete(int $id): void
    {
        $this->aula($id)->delete();

        session()->flash('success', 'Aula ao vivo removida.');
    }

    /**
     * Sempre buscada a partir da turma, então um id de outra turma dá 404.
     */
    private function aula(int $id): AulaAoVivo
    {
        return $this->turma->aulasAoVivo()->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.academico.aula-ao-vivo-manager', [
            'modulosDaTurma' => $this->turma->modulos()->orderBy('nome')->get(),
            'aulas' => $this->turma->aulasAoVivo()->with('conteudo.modulo')->reorder()->orderByRaw("case status when 'ao_vivo' then 0 when 'agendada' then 1 else 2 end")->orderBy('inicio_em')->get(),
        ]);
    }
}
