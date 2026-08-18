<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Dashboard</h1>

    <x-card>
        <p class="text-sm text-text-secondary">Bem-vindo, {{ auth()->user()->name }}.</p>
    </x-card>

    <x-card title="Notificações recentes" class="mt-6">
        @forelse ($recentNotifications as $notification)
            <div class="py-2 {{ !$loop->last ? 'border-b border-surface-border' : '' }}">
                <p class="text-sm text-text-primary font-medium">{{ $notification->data['title'] ?? 'Notificação' }}</p>
                <p class="text-xs text-text-secondary">{{ $notification->data['message'] ?? '' }} · {{ $notification->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-sm text-text-secondary">Nenhuma notificação registrada.</p>
        @endforelse
    </x-card>
</div>
