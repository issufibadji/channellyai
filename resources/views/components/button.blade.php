@props(['variant' => 'primary', 'type' => 'button'])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium text-sm transition disabled:opacity-50 '
        . match ($variant) {
            'primary' => 'bg-primary text-white hover:bg-primary-dark',
            'secondary' => 'bg-surface-card text-text-primary border border-surface-border hover:bg-surface-border',
            'danger' => 'bg-danger text-white hover:opacity-90',
            default => 'bg-primary text-white hover:bg-primary-dark',
        }
    ]) }}
>
    {{ $slot }}
</button>
