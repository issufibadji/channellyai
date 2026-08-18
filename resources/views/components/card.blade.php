@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-surface-card border border-surface-border rounded-xl p-6']) }}>
    @if ($title)
        <h2 class="text-lg font-semibold text-text-primary mb-4">{{ $title }}</h2>
    @endif

    {{ $slot }}
</div>
