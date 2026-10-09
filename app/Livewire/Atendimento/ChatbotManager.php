<?php

namespace App\Livewire\Atendimento;

use App\Contracts\PublicadorDeAgente;
use App\Models\Agente;
use App\Models\AgenteDadosNegocio;
use App\Models\AgenteServico;
use App\Models\Atendimento\Canal;
use App\Services\Agente\CanalConexaoProviderFactory;
use App\Services\Agente\NegocioMarkdownGenerator;
use App\Services\CurrentEstabelecimento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class ChatbotManager extends Component
{
    public Agente $agente;

    public string $template = 'barbearia-base';

    public string $nomeExibicao = '';

    public string $tomDeVoz = '';

    public string $endereco = '';

    public string $horarioFuncionamento = '';

    public string $antecedenciaMinima = '';

    public string $calendarId = '';

    public string $profissionais = '';

    public array $politicas = [];

    public string $contatoHumano = '';

    public string $mensagemEncaminhamento = '';

    public array $servicos = [];

    public ?string $preview = null;

    public bool $editando = false;

    /** @var array<string, array{conectado: bool, identificador: ?string}> */
    public array $canaisStatus = [];

    public ?string $canalAberto = null;

    public string $canalCredencialInput = '';

    public string $canalNome = '';

    public string $canalDescricao = '';

    public string $canalDescricaoCurta = '';

    public bool $canalPerfilCarregado = false;

    public function mount(): void
    {
        $estabelecimentoId = app(CurrentEstabelecimento::class)->id();

        if (! $estabelecimentoId) {
            session()->flash('warning', 'Selecione um estabelecimento no topo da página antes de configurar o agente.');
            $this->redirect(route('atendimento.dashboard'), navigate: false);

            return;
        }

        $nomeEstabelecimento = app(CurrentEstabelecimento::class)->estabelecimento()?->nome ?? 'Agente';

        $this->agente = Agente::firstOrCreate(
            ['estabelecimento_id' => $estabelecimentoId],
            ['nome' => $nomeEstabelecimento],
        );

        $this->carregarDados();

        $this->editando = $this->nomeExibicao === '';

        $this->atualizarStatusCanais();
    }

    public function editar(): void
    {
        $this->editando = true;
    }

    private function carregarDados(): void
    {
        $this->agente->load(['dadosNegocio', 'servicos']);
        $dados = $this->agente->dadosNegocio;

        $this->template = $this->agente->template ?: 'barbearia-base';
        $this->nomeExibicao = $dados?->nome_exibicao ?? '';
        $this->tomDeVoz = $dados?->tom_de_voz ?? '';
        $this->endereco = $dados?->endereco ?? '';
        $this->horarioFuncionamento = $dados?->horario_funcionamento ?? '';
        $this->antecedenciaMinima = $dados?->antecedencia_minima ?? '';
        $this->calendarId = $dados?->calendar_id ?? '';
        $this->profissionais = $dados?->profissionais ?? '';
        $this->politicas = $dados?->politicas ?? [];
        $this->contatoHumano = $dados?->contato_humano ?? '';
        $this->mensagemEncaminhamento = $dados?->mensagem_encaminhamento ?? '';

        $this->servicos = $this->agente->servicos->map(fn ($servico) => [
            'id' => $servico->id,
            'nome' => $servico->nome,
            'duracaoMinutos' => $servico->duracao_minutos,
            'preco' => (string) $servico->preco,
        ])->values()->all();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $this->validate([
            'template' => 'required|string|max:255',
            'nomeExibicao' => 'required|string|max:255',
            'antecedenciaMinima' => 'required|string|max:255',
            'calendarId' => 'nullable|string|max:255',
            'servicos.*.nome' => 'required|string|max:255',
            'servicos.*.duracaoMinutos' => 'required|integer|min:1',
            'servicos.*.preco' => 'required|numeric|min:0',
        ]);

        $this->agente->update(['template' => $this->template]);

        $this->agente->dadosNegocio()->updateOrCreate([], [
            'nome_exibicao' => $this->nomeExibicao,
            'tom_de_voz' => $this->tomDeVoz ?: null,
            'endereco' => $this->endereco ?: null,
            'horario_funcionamento' => $this->horarioFuncionamento ?: null,
            'antecedencia_minima' => $this->antecedenciaMinima,
            'calendar_id' => $this->calendarId ?: null,
            'profissionais' => $this->profissionais ?: null,
            'politicas' => $this->politicas,
            'contato_humano' => $this->contatoHumano ?: null,
            'mensagem_encaminhamento' => $this->mensagemEncaminhamento ?: null,
        ]);

        $idsMantidos = [];

        foreach ($this->servicos as $servico) {
            $registro = $this->agente->servicos()->updateOrCreate(
                ['id' => $servico['id'] ?? null],
                [
                    'nome' => $servico['nome'],
                    'duracao_minutos' => $servico['duracaoMinutos'],
                    'preco' => $servico['preco'],
                ],
            );

            $idsMantidos[] = $registro->id;
        }

        $this->agente->servicos()->whereNotIn('id', $idsMantidos ?: [0])->delete();

        session()->flash('success', 'Dados do agente salvos com sucesso.');

        $this->redirect(route('atendimento.dashboard'), navigate: false);
    }

    public function visualizar(NegocioMarkdownGenerator $generator): void
    {
        // Gera a partir do estado atual do formulário (ainda não salvo), não do banco.
        $dados = new AgenteDadosNegocio([
            'nome_exibicao' => $this->nomeExibicao,
            'tom_de_voz' => $this->tomDeVoz ?: null,
            'endereco' => $this->endereco ?: null,
            'horario_funcionamento' => $this->horarioFuncionamento ?: null,
            'antecedencia_minima' => $this->antecedenciaMinima,
            'calendar_id' => $this->calendarId ?: null,
            'profissionais' => $this->profissionais ?: null,
            'politicas' => $this->politicas,
            'contato_humano' => $this->contatoHumano ?: null,
            'mensagem_encaminhamento' => $this->mensagemEncaminhamento ?: null,
        ]);

        $servicos = collect($this->servicos)->map(fn ($servico) => new AgenteServico([
            'nome' => $servico['nome'],
            'duracao_minutos' => $servico['duracaoMinutos'],
            'preco' => $servico['preco'],
        ]));

        $this->agente->setRelation('dadosNegocio', $dados);
        $this->agente->setRelation('servicos', $servicos);

        $this->preview = $generator->gerar($this->agente);
    }

    public function publicar(PublicadorDeAgente $publicador, NegocioMarkdownGenerator $generator): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $this->agente->refresh();
        $this->agente->load(['dadosNegocio', 'servicos']);

        $markdown = $generator->gerar($this->agente);

        try {
            $publicador->publicar($this->agente, $markdown);

            session()->flash('success', 'Agente publicado com sucesso.');
        } catch (\Throwable) {
            session()->flash('error', 'Falha ao publicar o agente. Confira a configuração e tente de novo.');
        }

        $this->agente->refresh();
    }

    public function regenerarWebhookToken(): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $this->agente->regenerarWebhookToken();

        session()->flash('success', 'Token do webhook regenerado. Atualize onde ele for usado.');
    }

    private function atualizarStatusCanais(): void
    {
        $canais = Canal::whereIn('tipo', Canal::TIPOS_COM_PROVIDER)->get()->keyBy('tipo');

        $this->canaisStatus = [];

        foreach (Canal::TIPOS_COM_PROVIDER as $tipo) {
            $canal = $canais->get($tipo);
            $identificador = $canal?->configuracao['identificador'] ?? null;

            $this->canaisStatus[$tipo] = [
                'conectado' => $identificador !== null,
                'identificador' => $identificador,
            ];
        }
    }

    public function selecionarCanal(string $tipo): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $this->canalAberto = $this->canalAberto === $tipo ? null : $tipo;
        $this->canalCredencialInput = '';
        $this->canalPerfilCarregado = false;
        $this->resetErrorBag();

        if ($this->canalAberto && ($this->canaisStatus[$this->canalAberto]['conectado'] ?? false)) {
            $this->carregarPerfilCanal($this->canalAberto);
        }
    }

    public function conectarCanal(CanalConexaoProviderFactory $factory): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $tipo = $this->canalAberto;
        $provider = $tipo ? $factory->para($tipo) : null;
        abort_if(! $tipo || ! $provider, 404);

        $this->validate(['canalCredencialInput' => 'required|string']);

        try {
            $resultado = $provider->conectar(['token' => $this->canalCredencialInput]);

            $canal = Canal::firstOrCreate(
                ['tipo' => $tipo],
                ['nome' => (Canal::TIPOS[$tipo] ?? $tipo).' (agente)', 'ativo' => true],
            );

            $canal->update([
                'configuracao' => $resultado['configuracao'] + ['identificador' => $resultado['identificador']],
            ]);

            $this->canalCredencialInput = '';
            $this->atualizarStatusCanais();
            $this->carregarPerfilCanal($tipo);

            session()->flash('success', "{$resultado['identificador']} conectado com sucesso.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Falha ao conectar canal', ['tipo' => $tipo, 'erro' => $e->getMessage()]);
            session()->flash('error', 'Não foi possível conectar com essa credencial. Confira se foi copiada certo.');
        }
    }

    public function desconectarCanal(): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $tipo = $this->canalAberto;

        Canal::where('tipo', $tipo)->first()?->update(['configuracao' => null]);

        $this->canalPerfilCarregado = false;
        $this->atualizarStatusCanais();

        session()->flash('success', 'Canal desconectado do agente.');
    }

    private function carregarPerfilCanal(string $tipo): void
    {
        $provider = app(CanalConexaoProviderFactory::class)->para($tipo);
        $canal = Canal::where('tipo', $tipo)->first();

        if (! $provider || ! $provider->suportaPerfil() || ! $canal?->configuracao) {
            $this->canalPerfilCarregado = false;

            return;
        }

        try {
            $perfil = $provider->obterPerfil($canal->configuracao);

            $this->canalNome = $perfil['nome'];
            $this->canalDescricao = $perfil['descricao'];
            $this->canalDescricaoCurta = $perfil['descricao_curta'];
            $this->canalPerfilCarregado = true;
        } catch (\Throwable) {
            $this->canalPerfilCarregado = false;
            session()->flash('error', 'Não foi possível carregar os dados atuais do canal.');
        }
    }

    public function atualizarPerfilCanal(CanalConexaoProviderFactory $factory): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $this->validate([
            'canalNome' => 'required|string|max:64',
            'canalDescricao' => 'nullable|string|max:512',
            'canalDescricaoCurta' => 'nullable|string|max:120',
        ]);

        $tipo = $this->canalAberto;
        $provider = $tipo ? $factory->para($tipo) : null;
        $canal = $tipo ? Canal::where('tipo', $tipo)->first() : null;

        abort_if(! $provider || ! $canal?->configuracao, 404);

        try {
            $provider->atualizarPerfil($canal->configuracao, [
                'nome' => $this->canalNome,
                'descricao' => $this->canalDescricao,
                'descricao_curta' => $this->canalDescricaoCurta,
            ]);

            session()->flash('success', 'Perfil do canal atualizado.');
        } catch (\Throwable) {
            session()->flash('error', 'Falha ao atualizar o perfil do canal.');
        }
    }

    public function render()
    {
        return view('livewire.atendimento.chatbot-manager');
    }
}
