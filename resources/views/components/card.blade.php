@props(['title' => null, 'badge' => null])

<div {{ $attributes->merge(['class' => 'glow-border bg-surface-card/80 backdrop-blur border border-surface-border rounded-2xl p-6']) }}>
    @if ($title || $badge)
        <div class="flex items-center justify-between mb-4">
            @if ($title)
                <h2 class="text-lg font-semibold text-text-primary">{{ $title }}</h2>
            @endif

            @if ($badge)
                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-primary/15 text-accent">{{ $badge }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
