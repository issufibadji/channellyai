@props(['variant' => 'info', 'dismissible' => true])

@php
    $styles = match ($variant) {
        'success' => 'bg-success/10 text-success border-success/30',
        'error' => 'bg-danger/10 text-danger border-danger/30',
        'warning' => 'bg-warning/10 text-warning border-warning/30',
        default => 'bg-primary/10 text-primary border-primary/30',
    };
@endphp

<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif
    {{ $attributes->merge(['class' => "flex items-start justify-between gap-4 rounded-lg border px-4 py-3 text-sm $styles"]) }}
>
    <div>{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" @click="show = false" class="shrink-0 opacity-70 hover:opacity-100">&times;</button>
    @endif
</div>
