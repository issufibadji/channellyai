@props(['name', 'title' => null])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="$event.detail.name === '{{ $name }}' && (open = true)"
    x-on:close-modal.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/60"></div>

    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        @keydown.escape.window="open = false"
        class="relative w-full max-w-lg bg-surface-card border border-surface-border rounded-xl p-6"
    >
        @if ($title)
            <h2 class="text-lg font-semibold text-text-primary mb-4">{{ $title }}</h2>
        @endif

        {{ $slot }}
    </div>
</div>
