<?php

namespace App\Livewire\Academico;

use App\Models\Conteudo;
use App\Models\Modulo;
use App\Models\Turma;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.master')]
class ModuloConteudoManager extends Component
{
    use WithFileUploads;

    public Turma $turma;

    public ?int $moduloId = null;

    #[Validate('required|string|max:255')]
    public string $moduloNome = '';

    #[Validate('required|in:nivel,extra')]
    public string $categoria = 'nivel';

    public string $nivel = 'A1';

    public string $secao = '';

    public $capa = null;

    public ?string $capaAtual = null;

    #[Validate('required|integer|min:0')]
    public int $moduloOrdem = 0;

    public ?int $moduloAtualId = null;

    public ?int $conteudoId = null;

    #[Validate('required|string|max:255')]
    public string $titulo = '';

    #[Validate('required|in:video,video_curto,pdf,texto,exercicio,link')]
    public string $tipo = 'texto';

    public string $exercicioSubtipo = 'anexo';

    #[Validate('nullable|string')]
    public string $corpo = '';

    #[Validate('nullable|url')]
    public string $urlExterna = '';

    public $arquivo = null;

    /**
     * @var array<int, array{id: ?int, enunciado: string, ordem: int, opcoes: array<int, array{id: ?int, texto: string, correta: bool, ordem: int}>}>
     */
    public array $perguntas = [];

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
        $this->reset(['moduloId', 'moduloNome', 'categoria', 'nivel', 'secao', 'capa', 'capaAtual', 'moduloOrdem']);
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
        $this->secao = $modulo->secao ?? '';
        $this->capa = null;
        $this->capaAtual = $modulo->capa_path;
        $this->moduloOrdem = $modulo->ordem;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'modulo-form');
    }

    public function saveModulo(): void
    {
        $this->validate([
            'moduloNome' => 'required|string|max:255',
            'categoria' => 'required|in:nivel,extra',
            'secao' => 'nullable|string|max:100',
            'capa' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
            'moduloOrdem' => 'required|integer|min:0',
        ]);

        if ($this->categoria === 'nivel') {
            $this->validate(['nivel' => 'required|in:A1,A2,B1,B2,C1,C2']);
        }

        $modulo = $this->turma->modulos()->updateOrCreate(
            ['id' => $this->moduloId],
            [
                'nome' => $this->moduloNome,
                'categoria' => $this->categoria,
                'secao' => $this->secao ?: null,
                'nivel' => $this->categoria === 'nivel' ? $this->nivel : null,
                'ordem' => $this->moduloOrdem,
            ],
        );

        if ($this->capa) {
            if ($modulo->capa_path) {
                Storage::disk('public')->delete($modulo->capa_path);
            }

            $modulo->update(['capa_path' => $this->capa->store('modulos-capas', 'public')]);
        }

        $this->dispatch('close-modal');
        session()->flash('success', 'Módulo salvo com sucesso.');
    }

    public function deleteModulo(int $id): void
    {
        $modulo = Modulo::findOrFail($id);

        if ($modulo->capa_path) {
            Storage::disk('public')->delete($modulo->capa_path);
        }

        $modulo->delete();

        session()->flash('success', 'Módulo removido.');
    }

    public function createConteudo(int $moduloId): void
    {
        $this->reset([
            'conteudoId', 'titulo', 'tipo', 'exercicioSubtipo', 'corpo', 'urlExterna',
            'arquivo', 'perguntas', 'conteudoOrdem', 'diasLiberacao', 'bloqueado',
        ]);
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
        $this->exercicioSubtipo = $conteudo->exercicio_subtipo ?? 'anexo';
        $this->corpo = $conteudo->corpo ?? '';
        $this->urlExterna = $conteudo->url_externa ?? '';
        $this->arquivo = null;
        $this->conteudoOrdem = $conteudo->ordem;
        $this->diasLiberacao = $conteudo->dias_liberacao;
        $this->bloqueado = $conteudo->bloqueado;

        $this->perguntas = $conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo === 'quiz'
            ? $conteudo->perguntas()->with('opcoes')->get()->map(fn ($pergunta) => [
                'id' => $pergunta->id,
                'enunciado' => $pergunta->enunciado,
                'ordem' => $pergunta->ordem,
                'opcoes' => $pergunta->opcoes->map(fn ($opcao) => [
                    'id' => $opcao->id,
                    'texto' => $opcao->texto,
                    'correta' => $opcao->correta,
                    'ordem' => $opcao->ordem,
                ])->all(),
            ])->all()
            : [];

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'conteudo-form');
    }

    public function adicionarPergunta(): void
    {
        $this->perguntas[] = ['id' => null, 'enunciado' => '', 'ordem' => count($this->perguntas), 'opcoes' => []];
    }

    public function removerPergunta(int $index): void
    {
        unset($this->perguntas[$index]);
        $this->perguntas = array_values($this->perguntas);
    }

    public function adicionarOpcao(int $perguntaIndex): void
    {
        $this->perguntas[$perguntaIndex]['opcoes'][] = [
            'id' => null,
            'texto' => '',
            'correta' => false,
            'ordem' => count($this->perguntas[$perguntaIndex]['opcoes']),
        ];
    }

    public function removerOpcao(int $perguntaIndex, int $opcaoIndex): void
    {
        unset($this->perguntas[$perguntaIndex]['opcoes'][$opcaoIndex]);
        $this->perguntas[$perguntaIndex]['opcoes'] = array_values($this->perguntas[$perguntaIndex]['opcoes']);
    }

    public function saveConteudo(): void
    {
        $this->validate([
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|in:video,video_curto,pdf,texto,exercicio,link',
            'conteudoOrdem' => 'required|integer|min:0',
            'diasLiberacao' => 'required|integer|min:0',
        ]);

        if ($this->tipo === 'exercicio') {
            $this->validate(['exercicioSubtipo' => 'required|in:anexo,quiz']);
        }

        if (in_array($this->tipo, ['video', 'link'], true)) {
            $this->validate(['urlExterna' => 'required|url']);
        }

        $precisaDeArquivo = in_array($this->tipo, ['pdf', 'video_curto'], true)
            || ($this->tipo === 'exercicio' && $this->exercicioSubtipo === 'anexo');

        if ($precisaDeArquivo) {
            // Vídeo curto (30s–1min): sem ffprobe pra medir duração, o teto é de tamanho.
            $regra = match ($this->tipo) {
                'pdf' => 'mimes:pdf|max:20480',
                'video_curto' => 'mimetypes:video/mp4,video/webm,video/quicktime|max:51200',
                default => 'mimes:pdf,doc,docx|max:20480',
            };
            $this->validate(['arquivo' => "nullable|file|{$regra}"]);

            if (! $this->conteudoId && ! $this->arquivo) {
                $this->addError('arquivo', 'Envie um arquivo.');

                return;
            }
        }

        $ehQuiz = $this->tipo === 'exercicio' && $this->exercicioSubtipo === 'quiz';

        if ($ehQuiz) {
            $this->validate([
                'perguntas' => 'required|array|min:1',
                'perguntas.*.enunciado' => 'required|string',
                'perguntas.*.opcoes' => 'required|array|min:1',
                'perguntas.*.opcoes.*.texto' => 'required|string',
            ]);

            foreach ($this->perguntas as $i => $pergunta) {
                if (! collect($pergunta['opcoes'])->contains('correta', true)) {
                    $this->addError("perguntas.{$i}.opcoes", 'Cada pergunta precisa de pelo menos uma opção correta.');

                    return;
                }
            }
        }

        $conteudo = Conteudo::updateOrCreate(
            ['id' => $this->conteudoId],
            [
                'modulo_id' => $this->moduloAtualId,
                'titulo' => $this->titulo,
                'tipo' => $this->tipo,
                'exercicio_subtipo' => $this->tipo === 'exercicio' ? $this->exercicioSubtipo : null,
                'corpo' => $this->tipo === 'texto' ? ($this->corpo ?: null) : null,
                'url_externa' => in_array($this->tipo, ['video', 'link'], true) ? $this->urlExterna : null,
                'ordem' => $this->conteudoOrdem,
                'dias_liberacao' => $this->diasLiberacao,
                'bloqueado' => $this->bloqueado,
            ],
        );

        if ($precisaDeArquivo && $this->arquivo) {
            if ($conteudo->arquivo_path) {
                Storage::disk('public')->delete($conteudo->arquivo_path);
            }

            $conteudo->arquivo_path = $this->arquivo->store('conteudos', 'public');
            $conteudo->save();
        } elseif (! $precisaDeArquivo && $conteudo->arquivo_path) {
            Storage::disk('public')->delete($conteudo->arquivo_path);
            $conteudo->arquivo_path = null;
            $conteudo->save();
        }

        if ($ehQuiz) {
            $conteudo->perguntas()->delete();

            foreach ($this->perguntas as $i => $pergunta) {
                $novaPergunta = $conteudo->perguntas()->create([
                    'enunciado' => $pergunta['enunciado'],
                    'ordem' => $i,
                ]);

                foreach ($pergunta['opcoes'] as $j => $opcao) {
                    $novaPergunta->opcoes()->create([
                        'texto' => $opcao['texto'],
                        'correta' => (bool) ($opcao['correta'] ?? false),
                        'ordem' => $j,
                    ]);
                }
            }
        } else {
            $conteudo->perguntas()->delete();
        }

        $this->dispatch('close-modal');
        session()->flash('success', 'Conteúdo salvo com sucesso.');
    }

    public function deleteConteudo(int $id): void
    {
        $conteudo = Conteudo::findOrFail($id);

        if ($conteudo->arquivo_path) {
            Storage::disk('public')->delete($conteudo->arquivo_path);
        }

        $conteudo->delete();

        session()->flash('success', 'Conteúdo removido.');
    }

    public function render()
    {
        return view('livewire.academico.modulo-conteudo-manager', [
            'modulos' => $this->turma->modulos()->with('conteudos')->get(),
            'secoesExistentes' => $this->turma->modulos()->whereNotNull('secao')->distinct()->orderBy('secao')->pluck('secao'),
        ]);
    }
}
