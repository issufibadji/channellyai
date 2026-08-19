<aside class="w-64 shrink-0 bg-surface-card/70 backdrop-blur border-r border-surface-border flex flex-col h-screen sticky top-0">
    <div class="px-5 py-5 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30">
            <x-heroicon-s-sparkles class="w-5 h-5 text-white" />
        </div>
        <span class="text-lg font-semibold text-text-primary">{{ config('app.name') }}</span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 space-y-1">
        @foreach ($items as $item)
            @if ($item->children->isNotEmpty())
                <div x-data="{ open: {{ $item->children->contains(fn ($child) => request()->routeIs($child->route_name)) ? 'true' : 'false' }} }">
                    <button
                        @click="open = !open"
                        type="button"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
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
                                class="block px-3 py-2 rounded-xl text-sm transition {{ request()->routeIs($child->route_name) ? 'bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25' : 'text-text-secondary hover:bg-surface-border/60 hover:text-text-primary' }}"
                            >
                                {{ $child->label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ route($item->route_name) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition {{ request()->routeIs($item->route_name) ? 'bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25' : 'text-text-secondary hover:bg-surface-border/60 hover:text-text-primary' }}"
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
        <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-surface-border/60 transition">
            <div class="w-8 h-8 rounded-full overflow-hidden bg-linear-to-br from-primary to-accent flex items-center justify-center text-white text-xs font-semibold shrink-0">
                @if (auth()->user()->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" class="w-full h-full object-cover" alt="Avatar">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <span class="text-sm text-text-secondary truncate">{{ auth()->user()->name }}</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
            >
                <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5 shrink-0" />
                Sair
            </button>
        </form>
    </div>
</aside>
