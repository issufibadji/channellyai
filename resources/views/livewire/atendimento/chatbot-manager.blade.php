<div>
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-2xl font-semibold text-text-primary">IA e Chatbot</h1>
    </div>
    <p class="text-sm text-text-secondary mb-6">
        Dados do negócio usados pelo agente de IA deste estabelecimento. Pasta de publicação: <span class="font-mono">{{ $agente->group_folder }}</span>.
    </p>

    @if (session('success'))
        <x-alert variant="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    @if (session('error'))
        <x-alert variant="error" class="mb-6">{{ session('error') }}</x-alert>
    @endif

    @if (! $editando)
        <x-card class="max-w-3xl space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-text-primary">{{ $nomeExibicao }}</h2>
                <div class="flex gap-3">
                    <x-button type="button" variant="secondary" wire:click="visualizar">Pré-visualizar</x-button>
                    <x-button type="button" wire:click="editar">Editar</x-button>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-surface-border pt-4">
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-text-secondary">Publicação:</span>
                    <x-badge :variant="match ($agente->status_publicacao) {
                        'publicado' => 'success',
                        'erro' => 'danger',
                        default => 'default',
                    }">
                        {{ \App\Models\Agente::STATUS_PUBLICACAO[$agente->status_publicacao] ?? $agente->status_publicacao }}
                    </x-badge>
                    @if ($agente->publicado_em)
                        <span class="text-text-secondary">em {{ $agente->publicado_em->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
                <x-button type="button" variant="secondary" wire:click="publicar" wire:loading.attr="disabled">Publicar</x-button>
            </div>
            <p class="text-xs text-text-secondary -mt-2">
                Antes da primeira publicação, confirme que o agent group <span class="font-mono">{{ $agente->group_folder }}</span> já foi criado na VPS (<span class="font-mono">ncl groups create</span>).
            </p>

            <div class="border-t border-surface-border pt-4 space-y-2">
                <h3 class="text-sm font-medium text-text-primary">Integração (webhook do NanoClaw)</h3>
                <p class="text-xs text-text-secondary">
                    Endpoint: <span class="font-mono">POST /api/webhooks/nanoclaw</span>, header <span class="font-mono">X-NanoClaw-Token</span> com o valor abaixo. Esse token identifica este agente — não compartilhe fora do que vai consumir o webhook.
                </p>
                <div class="flex items-center gap-3">
                    <code class="flex-1 text-xs bg-surface border border-surface-border rounded-md px-3 py-2 text-text-secondary break-all">{{ $agente->webhook_token }}</code>
                    <x-button type="button" variant="secondary" wire:click="regenerarWebhookToken" wire:confirm="Regenerar o token? Qualquer integração usando o valor atual vai parar de funcionar." wire:loading.attr="disabled">
                        Regenerar
                    </x-button>
                </div>
            </div>

            <div class="border-t border-surface-border pt-4 space-y-3">
                <h3 class="text-sm font-medium text-text-primary">Bot do Telegram</h3>

                @if (! $agente->telegram_bot_username)
                    <p class="text-xs text-text-secondary">
                        Cole aqui o token que o <span class="font-mono">@BotFather</span> te deu ao criar o bot (comando <span class="font-mono">/newbot</span>). O token fica guardado de forma criptografada.
                    </p>
                    <div class="flex items-center gap-3">
                        <input type="password" wire:model="telegramBotTokenInput" placeholder="123456789:AAExemploDeTokenDoBotFather" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        <x-button type="button" wire:click="conectarTelegramBot" wire:loading.attr="disabled">Conectar</x-button>
                    </div>
                    @error('telegramBotTokenInput') <p class="text-sm text-danger">{{ $message }}</p> @enderror
                @else
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-text-secondary">Bot conectado: <span class="font-mono text-text-primary">@{{ $agente->telegram_bot_username }}</span></span>
                        <x-button type="button" variant="secondary" wire:click="desconectarTelegramBot" wire:confirm="Desconectar este bot do agente? Você pode reconectar colando o token de novo depois." wire:loading.attr="disabled">
                            Desconectar
                        </x-button>
                    </div>

                    @if ($telegramPerfilCarregado)
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium mb-1 text-text-primary">Nome do bot</label>
                                <input type="text" wire:model="telegramNome" maxlength="64" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                                @error('telegramNome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1 text-text-primary">Descrição (aparece antes do /start)</label>
                                <textarea wire:model="telegramDescricao" maxlength="512" rows="2" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                                @error('telegramDescricao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1 text-text-primary">Descrição curta (perfil do bot)</label>
                                <input type="text" wire:model="telegramDescricaoCurta" maxlength="120" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                                @error('telegramDescricaoCurta') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                            </div>
                            <x-button type="button" wire:click="atualizarPerfilTelegram" wire:loading.attr="disabled">Atualizar no Telegram</x-button>
                        </div>
                    @else
                        <p class="text-xs text-danger">Não foi possível carregar os dados atuais do bot. Tente recarregar a página.</p>
                    @endif
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-text-secondary">Template:</span> {{ $template }}</div>
                <div><span class="text-text-secondary">Tom de voz:</span> {{ $tomDeVoz ?: '—' }}</div>
                <div><span class="text-text-secondary">Endereço:</span> {{ $endereco ?: '—' }}</div>
                <div><span class="text-text-secondary">Horário:</span> {{ $horarioFuncionamento ?: '—' }}</div>
                <div><span class="text-text-secondary">Antecedência mínima:</span> {{ $antecedenciaMinima ?: '—' }}</div>
                <div class="sm:col-span-2"><span class="text-text-secondary">Google Calendar:</span> {{ $calendarId ?: '—' }}</div>
                <div class="sm:col-span-2"><span class="text-text-secondary">Profissionais:</span> {{ $profissionais ?: '—' }}</div>
            </div>

            <div>
                <h3 class="text-sm font-medium text-text-primary mb-2">Serviços</h3>
                @forelse ($servicos as $servico)
                    <div class="text-sm text-text-secondary">{{ $servico['nome'] }} — {{ $servico['duracaoMinutos'] }} min — R$ {{ number_format((float) $servico['preco'], 2, ',', '.') }}</div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhum serviço cadastrado.</p>
                @endforelse
            </div>

            <div>
                <h3 class="text-sm font-medium text-text-primary mb-2">Políticas</h3>
                @forelse ($politicas as $politica)
                    <div class="text-sm text-text-secondary">{{ $politica }}</div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhuma política cadastrada.</p>
                @endforelse
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-text-secondary">Contato humano:</span> {{ $contatoHumano ?: '—' }}</div>
                <div><span class="text-text-secondary">Mensagem de encaminhamento:</span> {{ $mensagemEncaminhamento ?: '—' }}</div>
            </div>
        </x-card>
    @else
    <form wire:submit="save" class="space-y-6 max-w-3xl">
        <x-card>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Nome de exibição</label>
                    <input type="text" wire:model="nomeExibicao" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('nomeExibicao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Template</label>
                    <input type="text" wire:model="template" placeholder="ex.: barbearia-base" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('template') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Tom de voz</label>
                    <input type="text" wire:model="tomDeVoz" placeholder="ex.: cordial e direto" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1 text-text-primary">Endereço</label>
                    <input type="text" wire:model="endereco" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Horário de funcionamento</label>
                    <input type="text" wire:model="horarioFuncionamento" placeholder="ex.: seg a sáb, 9h às 19h" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Antecedência mínima para agendar</label>
                    <input type="text" wire:model="antecedenciaMinima" placeholder="ex.: 2 horas" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('antecedenciaMinima') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1 text-text-primary">Google Calendar (calendarId)</label>
                    <input type="text" wire:model="calendarId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1 text-text-primary">Profissionais</label>
                    <textarea wire:model="profissionais" rows="2" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                </div>
            </div>
        </x-card>

        <x-card x-data="{ servicos: $wire.entangle('servicos') }">
            <h2 class="font-semibold text-text-primary mb-4">Serviços</h2>

            <div class="space-y-3">
                <template x-for="(servico, index) in servicos" :key="index">
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_140px_140px_auto] gap-3 items-start">
                        <div>
                            <input type="text" x-model="servico.nome" placeholder="Nome do serviço" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        </div>
                        <div>
                            <input type="number" min="1" x-model="servico.duracaoMinutos" placeholder="Minutos" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        </div>
                        <div>
                            <input type="number" step="0.01" min="0" x-model="servico.preco" placeholder="Preço" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        </div>
                        <button type="button" @click="servicos.splice(index, 1)" class="text-danger hover:underline text-sm pt-2">Remover</button>
                    </div>
                </template>
            </div>

            <button
                type="button"
                @click="servicos.push({ id: null, nome: '', duracaoMinutos: '', preco: '' })"
                class="mt-3 text-primary hover:underline text-sm"
            >
                + Adicionar serviço
            </button>

            @error('servicos.*.nome') <p class="mt-2 text-sm text-danger">Preencha o nome de todos os serviços.</p> @enderror
            @error('servicos.*.duracaoMinutos') <p class="mt-2 text-sm text-danger">Preencha a duração (em minutos) de todos os serviços.</p> @enderror
            @error('servicos.*.preco') <p class="mt-2 text-sm text-danger">Preencha o preço de todos os serviços.</p> @enderror
        </x-card>

        <x-card x-data="{ politicas: $wire.entangle('politicas'), novaPolitica: '' }">
            <h2 class="font-semibold text-text-primary mb-4">Políticas</h2>

            <ul class="space-y-2 mb-3">
                <template x-for="(politica, index) in politicas" :key="index">
                    <li class="flex items-center justify-between gap-3 text-sm text-text-secondary">
                        <span x-text="politica"></span>
                        <button type="button" @click="politicas.splice(index, 1)" class="text-danger hover:underline">Remover</button>
                    </li>
                </template>
            </ul>

            <div class="flex gap-3">
                <input
                    type="text"
                    x-model="novaPolitica"
                    @keydown.enter.prevent="if (novaPolitica.trim()) { politicas.push(novaPolitica); novaPolitica = '' }"
                    placeholder="Nova política"
                    class="w-full rounded-md bg-surface border-surface-border text-text-primary"
                >
                <x-button
                    type="button"
                    variant="secondary"
                    @click="if (novaPolitica.trim()) { politicas.push(novaPolitica); novaPolitica = '' }"
                >
                    Adicionar
                </x-button>
            </div>
        </x-card>

        <x-card>
            <h2 class="font-semibold text-text-primary mb-4">Encaminhamento humano</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Contato</label>
                    <input type="text" wire:model="contatoHumano" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Mensagem</label>
                    <input type="text" wire:model="mensagemEncaminhamento" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
            </div>
        </x-card>

        <div class="flex items-center justify-end gap-3">
            <span wire:loading class="text-sm text-text-secondary">Processando...</span>
            <x-button type="button" variant="secondary" wire:click="visualizar" wire:loading.attr="disabled">Pré-visualizar</x-button>
            <x-button type="submit" wire:loading.attr="disabled">Salvar</x-button>
        </div>
    </form>
    @endif

    @if ($preview)
        <x-card class="max-w-3xl mt-6">
            <h2 class="font-semibold text-text-primary mb-3">Pré-visualização do negocio.md</h2>
            <pre class="whitespace-pre-wrap text-sm text-text-secondary font-mono">{{ $preview }}</pre>
        </x-card>
    @endif
</div>
