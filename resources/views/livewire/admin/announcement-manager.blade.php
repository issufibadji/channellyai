<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-2">Anúncios</h1>
    <p class="text-sm text-text-secondary mb-6">Envie avisos para os usuários do sistema, com controle de canal de entrega.</p>

    <x-card>
        <form wire:submit="send" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1 text-text-primary">Título do anúncio</label>
                <input type="text" wire:model="title" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('title') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Público alvo</label>
                <select wire:model="target" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="all">Todos os usuários</option>
                    @foreach ($roles as $role)
                        <option value="role:{{ $role->name }}">Por papel: {{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Canais</label>
                <div class="flex flex-wrap gap-4 pt-2">
                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" wire:model="channels" value="database" class="rounded bg-surface border-surface-border">
                        Interno (banco)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" wire:model="channels" value="webhook" class="rounded bg-surface border-surface-border">
                        Webhook
                    </label>
                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                        <input type="checkbox" wire:model="channels" value="webpush" class="rounded bg-surface border-surface-border">
                        Push
                    </label>
                </div>
                @error('channels') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1 text-text-primary">Mensagem</label>
                <textarea wire:model="message" rows="4" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                @error('message') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2 flex justify-end">
                <x-button type="submit">Enviar anúncio</x-button>
            </div>
        </form>
    </x-card>

    <div class="mt-6">
        <x-table :headers="['Título', 'Público', 'Canais', 'Enviado em', 'Ações']">
            @foreach ($announcements as $announcement)
                <tr>
                    <td class="px-4 py-3">{{ $announcement->title }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ $announcement->target === 'all' ? 'Todos' : $announcement->target }}</td>
                    <td class="px-4 py-3">
                        @foreach ($announcement->channels as $channel)
                            <x-badge variant="primary">{{ $channel }}</x-badge>
                        @endforeach
                    </td>
                    <td class="px-4 py-3 text-text-secondary">{{ $announcement->created_at->format('d/m/Y, H:i:s') }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button
                            wire:click="delete({{ $announcement->id }})"
                            wire:confirm="Remover este registro do histórico?"
                            class="text-danger hover:underline text-sm"
                        >
                            Excluir
                        </button>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">
            {{ $announcements->links() }}
        </div>
    </div>
</div>
