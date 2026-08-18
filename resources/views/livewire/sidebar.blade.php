<aside class="w-60 shrink-0 bg-surface-card border-r border-surface-border flex flex-col h-screen sticky top-0">
    <div class="px-6 py-5">
        <span class="text-lg font-semibold text-text-primary">{{ config('app.name') }}</span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 space-y-1">
        @foreach ($items as $item)
            @if ($item->children->isNotEmpty())
                <div x-data="{ open: {{ $item->children->contains(fn ($child) => request()->routeIs($child->route_name)) ? 'true' : 'false' }} }">
                    <button
                        @click="open = !open"
                        type="button"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-text-secondary hover:bg-surface-border"
                    >
                        @if ($item->icon)
                            <x-icon :name="'heroicon-o-'.$item->icon" class="w-5 h-5 shrink-0" />
                        @endif
                        <span class="flex-1 text-left">{{ $item->label }}</span>
                    </button>

                    <div x-show="open" x-transition class="ml-8 mt-1 space-y-1">
                        @foreach ($item->children as $child)
                            <a
                                href="{{ route($child->route_name) }}"
                                class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs($child->route_name) ? 'bg-gradient-to-r from-primary to-primary-dark text-white' : 'text-text-secondary hover:bg-surface-border' }}"
                            >
                                {{ $child->label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ route($item->route_name) }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm {{ request()->routeIs($item->route_name) ? 'bg-gradient-to-r from-primary to-primary-dark text-white' : 'text-text-secondary hover:bg-surface-border' }}"
                >
                    @if ($item->icon)
                        <x-icon :name="'heroicon-o-'.$item->icon" class="w-5 h-5 shrink-0" />
                    @endif
                    <span>{{ $item->label }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="px-3 py-4 border-t border-surface-border">
        <div class="px-3 py-2 text-sm text-text-secondary truncate">{{ auth()->user()->name }}</div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-text-secondary hover:bg-surface-border"
            >
                <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5 shrink-0" />
                Sair
            </button>
        </form>
    </div>
</aside>
