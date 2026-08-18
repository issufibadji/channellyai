@props(['variant' => 'default'])

@php
    $styles = match ($variant) {
        'success' => 'bg-success/10 text-success',
        'danger' => 'bg-danger/10 text-danger',
        'warning' => 'bg-warning/10 text-warning',
        'primary' => 'bg-primary/10 text-primary',
        default => 'bg-surface-border text-text-secondary',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium $styles"]) }}>
    {{ $slot }}
</span>
