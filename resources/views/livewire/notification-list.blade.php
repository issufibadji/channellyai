<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Notificações</h1>
        <x-button variant="secondary" wire:click="markAllAsRead">Marcar todas como lidas</x-button>
    </div>

    <x-card>
        @forelse ($notifications as $notification)
            <div
                wire:click="markAsRead('{{ $notification->id }}')"
                class="py-3 cursor-pointer {{ !$loop->last ? 'border-b border-surface-border' : '' }} {{ $notification->read_at ? 'opacity-60' : '' }}"
            >
                <p class="text-sm text-text-primary font-medium">{{ $notification->data['title'] ?? 'Notificação' }}</p>
                <p class="text-sm text-text-secondary">{{ $notification->data['message'] ?? '' }}</p>
                <p class="text-xs text-text-secondary mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-sm text-text-secondary">Nenhuma notificação registrada.</p>
        @endforelse
    </x-card>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
