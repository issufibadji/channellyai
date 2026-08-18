<div wire:poll.30s x-data="{ open: false }" class="relative">
    <button @click="open = !open" type="button" class="relative p-2 rounded-lg hover:bg-surface-border">
        <x-heroicon-o-bell class="w-5 h-5 text-text-secondary" />
        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center w-4 h-4 rounded-full bg-danger text-white text-[10px]">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        x-cloak
        class="absolute right-0 mt-2 w-80 bg-surface-card border border-surface-border rounded-xl shadow-lg z-40"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-surface-border">
            <span class="text-sm font-medium text-text-primary">Notificações</span>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-primary hover:underline">Marcar todas como lidas</button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-surface-border">
            @forelse ($notifications as $notification)
                <button
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="w-full text-left px-4 py-3 text-sm hover:bg-surface {{ $notification->read_at ? 'opacity-60' : '' }}"
                >
                    <p class="text-text-primary font-medium">{{ $notification->data['title'] ?? 'Notificação' }}</p>
                    <p class="text-text-secondary text-xs mt-0.5">{{ $notification->data['message'] ?? '' }}</p>
                    <p class="text-text-secondary text-xs mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </button>
            @empty
                <p class="px-4 py-6 text-center text-sm text-text-secondary">Nenhuma notificação registrada.</p>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}" class="block text-center px-4 py-3 text-sm text-primary hover:underline border-t border-surface-border">
            Ver todas
        </a>

        <button
            type="button"
            onclick="window.subscribeToPush()"
            class="block w-full text-center px-4 py-2 text-xs text-text-secondary hover:text-text-primary border-t border-surface-border"
        >
            Ativar notificações push neste dispositivo
        </button>
    </div>
</div>
