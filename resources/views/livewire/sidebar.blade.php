<aside
    x-data="{ collapsed: localStorage.getItem('sidebar-collapsed') === 'true' }"
    x-bind:class="collapsed ? 'w-20' : 'w-64'"
    class="shrink-0 bg-surface-card/70 backdrop-blur border-r border-surface-border flex flex-col h-screen sticky top-0 transition-all duration-200"
>
    <div class="px-5 py-5 flex items-center gap-3" x-bind:class="collapsed && 'justify-center px-0'">
        <div class="w-9 h-9 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30 shrink-0">
            <x-heroicon-s-sparkles class="w-5 h-5 text-white" />
        </div>
        <span x-show="!collapsed" x-cloak class="text-lg font-semibold text-text-primary whitespace-nowrap">{{ config('app.name') }}</span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 space-y-1">
        @foreach ($items as $item)
            @if ($item->children->isNotEmpty())
                <div x-data="{ open: {{ $item->children->contains(fn ($child) => request()->routeIs($child->route_name)) ? 'true' : 'false' }} }">
                    <button
                        @click="collapsed ? (collapsed = false, open = true, localStorage.setItem('sidebar-collapsed', false)) : (open = !open)"
                        type="button"
                        title="{{ $item->label }}"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
                        x-bind:class="collapsed && 'justify-center px-0'"
                    >
                        @if ($item->hasValidIcon())
                            <x-icon :name="'heroicon-o-'.$item->icon" class="w-5 h-5 shrink-0" />
                        @endif
                        <span x-show="!collapsed" x-cloak class="flex-1 text-left whitespace-nowrap">{{ $item->label }}</span>
                        <x-heroicon-o-chevron-down x-show="!collapsed" x-cloak class="w-4 h-4 shrink-0 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                    </button>

                    <div x-show="open && !collapsed" x-transition class="ml-8 mt-1 space-y-1">
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
                    title="{{ $item->label }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition {{ request()->routeIs($item->route_name) ? 'bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25' : 'text-text-secondary hover:bg-surface-border/60 hover:text-text-primary' }}"
                    x-bind:class="collapsed && 'justify-center px-0'"
                >
                    @if ($item->hasValidIcon())
                        <x-icon :name="'heroicon-o-'.$item->icon" class="w-5 h-5 shrink-0" />
                    @endif
                    <span x-show="!collapsed" x-cloak class="whitespace-nowrap">{{ $item->label }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="px-3 py-3 border-t border-surface-border">
        <button
            type="button"
            @click="collapsed = !collapsed; localStorage.setItem('sidebar-collapsed', collapsed)"
            title="{{ __('Recolher menu') }}"
            class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
            x-bind:class="collapsed && 'justify-center px-0'"
        >
            <x-heroicon-o-chevron-double-left x-show="!collapsed" class="w-5 h-5 shrink-0" />
            <x-heroicon-o-chevron-double-right x-show="collapsed" x-cloak class="w-5 h-5 shrink-0" />
            <span x-show="!collapsed" x-cloak class="whitespace-nowrap">Recolher menu</span>
        </button>
    </div>

    <div class="px-3 py-4 border-t border-surface-border">
        <a href="{{ route('settings.profile') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-surface-border/60 transition" x-bind:class="collapsed && 'justify-center px-0'">
            <div class="w-8 h-8 rounded-full overflow-hidden bg-linear-to-br from-primary to-accent flex items-center justify-center text-white text-xs font-semibold shrink-0">
                @if (auth()->user()->avatar_path)
                    <img src="{{ asset('storage/'.auth()->user()->avatar_path) }}" class="w-full h-full object-cover" alt="Avatar">
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>
            <span x-show="!collapsed" x-cloak class="text-sm text-text-secondary truncate whitespace-nowrap">{{ auth()->user()->name }}</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                title="Sair"
                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-text-secondary hover:bg-surface-border/60 hover:text-text-primary transition"
                x-bind:class="collapsed && 'justify-center px-0'"
            >
                <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="whitespace-nowrap">Sair</span>
            </button>
        </form>
    </div>
</aside>
