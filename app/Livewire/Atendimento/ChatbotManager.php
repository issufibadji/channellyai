<?php

namespace App\Livewire\Atendimento;

use App\Models\Agente;
use App\Models\AgenteDadosNegocio;
use App\Models\AgenteServico;
use App\Services\Agente\NegocioMarkdownGenerator;
use App\Services\CurrentEstabelecimento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class ChatbotManager extends Component
{
    public Agente $agente;

    public string $nomeExibicao = '';

    public string $tomDeVoz = '';

    public string $endereco = '';

    public string $horarioFuncionamento = '';

    public string $antecedenciaMinima = '';

    public string $calendarId = '';

    public string $profissionais = '';

    public array $politicas = [];

    public string $novaPolitica = '';

    public string $contatoHumano = '';

    public string $mensagemEncaminhamento = '';

    public array $servicos = [];

    public ?string $preview = null;

    public function mount(): void
    {
        $estabelecimentoId = app(CurrentEstabelecimento::class)->id();
        $nomeEstabelecimento = app(CurrentEstabelecimento::class)->estabelecimento()?->nome ?? 'Agente';

        $this->agente = Agente::firstOrCreate(
            ['estabelecimento_id' => $estabelecimentoId],
            ['nome' => $nomeEstabelecimento],
        );

        $this->carregarDados();
    }

    private function carregarDados(): void
    {
        $this->agente->load(['dadosNegocio', 'servicos']);
        $dados = $this->agente->dadosNegocio;

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

    public function addServico(): void
    {
        $this->servicos[] = ['id' => null, 'nome' => '', 'duracaoMinutos' => '', 'preco' => ''];
    }

    public function removeServico(int $index): void
    {
        unset($this->servicos[$index]);
        $this->servicos = array_values($this->servicos);
    }

    public function addPolitica(): void
    {
        if (trim($this->novaPolitica) !== '') {
            $this->politicas[] = $this->novaPolitica;
            $this->novaPolitica = '';
        }
    }

    public function removePolitica(int $index): void
    {
        unset($this->politicas[$index]);
        $this->politicas = array_values($this->politicas);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('manage-chatbot'), 403);

        $this->validate([
            'nomeExibicao' => 'required|string|max:255',
            'antecedenciaMinima' => 'required|string|max:255',
            'calendarId' => 'nullable|string|max:255',
            'servicos.*.nome' => 'required|string|max:255',
            'servicos.*.duracaoMinutos' => 'required|integer|min:1',
            'servicos.*.preco' => 'required|numeric|min:0',
        ]);

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

        $this->carregarDados();

        session()->flash('success', 'Dados do agente salvos com sucesso.');
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

    public function render()
    {
        return view('livewire.atendimento.chatbot-manager');
    }
}
