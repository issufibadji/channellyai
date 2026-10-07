<div>
    @if ($estabelecimentos->count() > 1)
        <div class="relative" x-data="{ open: false }">
            <button
                type="button"
                @click="open = !open"
                @click.outside="open = false"
                class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-surface-border text-text-secondary hover:text-text-primary transition text-sm"
            >
                <x-heroicon-o-building-office-2 class="w-5 h-5" />
                {{ $atual?->nome ?? 'Selecionar estabelecimento' }}
                <x-heroicon-o-chevron-down class="w-4 h-4" />
            </button>

            <div
                x-show="open"
                x-cloak
                class="absolute right-0 mt-2 w-56 rounded-lg bg-surface-card border border-surface-border shadow-lg z-40 py-1"
            >
                @foreach ($estabelecimentos as $estabelecimento)
                    <button
                        type="button"
                        wire:click="switchTo({{ $estabelecimento->id }})"
                        @click="open = false"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-surface-border {{ $atual?->id === $estabelecimento->id ? 'text-primary' : 'text-text-secondary' }}"
                    >
                        {{ $estabelecimento->nome }}
                    </button>
                @endforeach
            </div>
        </div>
    @elseif ($atual)
        <span class="flex items-center gap-2 px-3 py-2 text-sm text-text-secondary">
            <x-heroicon-o-building-office-2 class="w-5 h-5" />
            {{ $atual->nome }}
        </span>
    @endif
</div>
