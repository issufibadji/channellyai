@props(['variant' => 'primary', 'type' => 'button'])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center px-4 py-2 rounded-full font-medium text-sm transition disabled:opacity-50 '
        . match ($variant) {
            'primary' => 'bg-linear-to-r from-primary to-accent text-white shadow-lg shadow-primary/25 hover:brightness-110',
            'secondary' => 'bg-surface-card text-text-primary border border-surface-border hover:bg-surface-border',
            'danger' => 'bg-danger text-white hover:opacity-90',
            default => 'bg-linear-to-r from-primary to-accent text-white shadow-lg shadow-primary/25 hover:brightness-110',
        }
    ]) }}
>
    {{ $slot }}
</button>
