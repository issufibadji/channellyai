<div>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30">
            <x-heroicon-s-squares-2x2 class="w-5 h-5 text-white" />
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">Dashboard</h1>
            <p class="text-sm text-text-secondary">Bem-vindo de volta, {{ auth()->user()->name }}.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Sua função</p>
            <p class="mt-2 text-2xl font-semibold text-text-primary capitalize">{{ $roleName }}</p>
        </x-card>

        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Notificações não lidas</p>
            <p class="mt-2 text-2xl font-semibold text-accent">{{ $unreadCount }}</p>
        </x-card>

        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Membro desde</p>
            <p class="mt-2 text-2xl font-semibold text-text-primary">{{ $memberSince->format('d/m/Y') }}</p>
        </x-card>
    </div>

    <x-card title="Atividade recente" :badge="$recentNotifications->isNotEmpty() ? $recentNotifications->count().' eventos' : null">
        @forelse ($recentNotifications as $notification)
            <div class="flex items-start gap-3 py-3 {{ !$loop->last ? 'border-b border-surface-border' : '' }}">
                <div class="w-8 h-8 rounded-full bg-primary/15 flex items-center justify-center shrink-0 mt-0.5">
                    <x-heroicon-o-bell class="w-4 h-4 text-accent" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-text-primary font-medium">{{ $notification->data['title'] ?? 'Notificação' }}</p>
                    <p class="text-xs text-text-secondary mt-0.5">{{ $notification->data['message'] ?? '' }}</p>
                    <p class="text-xs text-text-secondary/70 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-text-secondary">Nenhuma notificação registrada.</p>
        @endforelse
    </x-card>
</div>
