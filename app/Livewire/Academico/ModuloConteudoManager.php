<?php

namespace App\Livewire\Academico;

use App\Models\Conteudo;
use App\Models\Modulo;
use App\Models\Turma;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.master')]
class ModuloConteudoManager extends Component
{
    public Turma $turma;

    public ?int $moduloId = null;

    #[Validate('required|string|max:255')]
    public string $moduloNome = '';

    #[Validate('required|in:nivel,extra')]
    public string $categoria = 'nivel';

    public string $nivel = 'A1';

    #[Validate('required|integer|min:0')]
    public int $moduloOrdem = 0;

    public ?int $moduloAtualId = null;

    public ?int $conteudoId = null;

    #[Validate('required|string|max:255')]
    public string $titulo = '';

    #[Validate('required|in:video,pdf,texto,exercicio,link')]
    public string $tipo = 'texto';

    #[Validate('nullable|string')]
    public string $corpo = '';

    #[Validate('nullable|url')]
    public string $urlExterna = '';

    #[Validate('required|integer|min:0')]
    public int $conteudoOrdem = 0;

    #[Validate('required|integer|min:0')]
    public int $diasLiberacao = 0;

    public bool $bloqueado = false;

    public function mount(Turma $turma): void
    {
        Gate::authorize('manageConteudo', $turma);

        $this->turma = $turma;
    }

    public function createModulo(): void
    {
        $this->reset(['moduloId', 'moduloNome', 'categoria', 'nivel', 'moduloOrdem']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'modulo-form');
    }

    public function editModulo(int $id): void
    {
        $modulo = Modulo::findOrFail($id);

        $this->moduloId = $modulo->id;
        $this->moduloNome = $modulo->nome;
        $this->categoria = $modulo->categoria;
        $this->nivel = $modulo->nivel ?? 'A1';
        $this->moduloOrdem = $modulo->ordem;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'modulo-form');
    }

    public function saveModulo(): void
    {
        $this->validate([
            'moduloNome' => 'required|string|max:255',
            'categoria' => 'required|in:nivel,extra',
            'moduloOrdem' => 'required|integer|min:0',
        ]);

        if ($this->categoria === 'nivel') {
            $this->validate(['nivel' => 'required|in:A1,A2,B1,B2,C1,C2']);
        }

        $this->turma->modulos()->updateOrCreate(
            ['id' => $this->moduloId],
            [
                'nome' => $this->moduloNome,
                'categoria' => $this->categoria,
                'nivel' => $this->categoria === 'nivel' ? $this->nivel : null,
                'ordem' => $this->moduloOrdem,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Módulo salvo com sucesso.');
    }

    public function deleteModulo(int $id): void
    {
        Modulo::findOrFail($id)->delete();

        session()->flash('success', 'Módulo removido.');
    }

    public function createConteudo(int $moduloId): void
    {
        $this->reset(['conteudoId', 'titulo', 'tipo', 'corpo', 'urlExterna', 'conteudoOrdem', 'diasLiberacao', 'bloqueado']);
        $this->moduloAtualId = $moduloId;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'conteudo-form');
    }

    public function editConteudo(int $id): void
    {
        $conteudo = Conteudo::findOrFail($id);

        $this->conteudoId = $conteudo->id;
        $this->moduloAtualId = $conteudo->modulo_id;
        $this->titulo = $conteudo->titulo;
        $this->tipo = $conteudo->tipo;
        $this->corpo = $conteudo->corpo ?? '';
        $this->urlExterna = $conteudo->url_externa ?? '';
        $this->conteudoOrdem = $conteudo->ordem;
        $this->diasLiberacao = $conteudo->dias_liberacao;
        $this->bloqueado = $conteudo->bloqueado;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'conteudo-form');
    }

    public function saveConteudo(): void
    {
        $this->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|in:video,pdf,texto,exercicio,link',
            'corpo' => 'nullable|string',
            'urlExterna' => 'nullable|url',
            'conteudoOrdem' => 'required|integer|min:0',
            'diasLiberacao' => 'required|integer|min:0',
        ]);

        Conteudo::updateOrCreate(
            ['id' => $this->conteudoId],
            [
                'modulo_id' => $this->moduloAtualId,
                'titulo' => $this->titulo,
                'tipo' => $this->tipo,
                'corpo' => $this->corpo ?: null,
                'url_externa' => $this->urlExterna ?: null,
                'ordem' => $this->conteudoOrdem,
                'dias_liberacao' => $this->diasLiberacao,
                'bloqueado' => $this->bloqueado,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Conteúdo salvo com sucesso.');
    }

    public function deleteConteudo(int $id): void
    {
        Conteudo::findOrFail($id)->delete();

        session()->flash('success', 'Conteúdo removido.');
    }

    public function render()
    {
        return view('livewire.academico.modulo-conteudo-manager', [
            'modulos' => $this->turma->modulos()->with('conteudos')->get(),
        ]);
    }
}
