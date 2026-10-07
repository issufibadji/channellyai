<div>
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-2xl font-semibold text-text-primary">IA e Chatbot</h1>
    </div>
    <p class="text-sm text-text-secondary mb-6">
        Dados do negócio usados pelo agente de IA deste estabelecimento. Pasta de publicação: <span class="font-mono">{{ $agente->group_folder }}</span>.
    </p>

    <form wire:submit="save" class="space-y-6 max-w-3xl">
        <x-card>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Nome de exibição</label>
                    <input type="text" wire:model="nomeExibicao" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('nomeExibicao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
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

        <x-card>
            <h2 class="font-semibold text-text-primary mb-4">Serviços</h2>

            <div class="space-y-3">
                @foreach ($servicos as $index => $servico)
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_140px_140px_auto] gap-3 items-start">
                        <div>
                            <input type="text" wire:model="servicos.{{ $index }}.nome" placeholder="Nome do serviço" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error("servicos.{$index}.nome") <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input type="number" min="1" wire:model="servicos.{{ $index }}.duracaoMinutos" placeholder="Minutos" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error("servicos.{$index}.duracaoMinutos") <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <input type="number" step="0.01" min="0" wire:model="servicos.{{ $index }}.preco" placeholder="Preço" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error("servicos.{$index}.preco") <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <button type="button" wire:click="removeServico({{ $index }})" class="text-danger hover:underline text-sm pt-2">Remover</button>
                    </div>
                @endforeach
            </div>

            <button type="button" wire:click="addServico" class="mt-3 text-primary hover:underline text-sm">+ Adicionar serviço</button>
        </x-card>

        <x-card>
            <h2 class="font-semibold text-text-primary mb-4">Políticas</h2>

            <ul class="space-y-2 mb-3">
                @foreach ($politicas as $index => $politica)
                    <li class="flex items-center justify-between gap-3 text-sm text-text-secondary">
                        <span>{{ $politica }}</span>
                        <button type="button" wire:click="removePolitica({{ $index }})" class="text-danger hover:underline">Remover</button>
                    </li>
                @endforeach
            </ul>

            <div class="flex gap-3">
                <input type="text" wire:model="novaPolitica" placeholder="Nova política" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                <x-button type="button" variant="secondary" wire:click="addPolitica">Adicionar</x-button>
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

        <div class="flex justify-end gap-3">
            <x-button type="button" variant="secondary" wire:click="visualizar">Pré-visualizar</x-button>
            <x-button type="submit">Salvar</x-button>
        </div>
    </form>

    @if ($preview)
        <x-card class="max-w-3xl mt-6">
            <h2 class="font-semibold text-text-primary mb-3">Pré-visualização do negocio.md</h2>
            <pre class="whitespace-pre-wrap text-sm text-text-secondary font-mono">{{ $preview }}</pre>
        </x-card>
    @endif
</div>
