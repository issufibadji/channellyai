@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'bg-surface-card border border-surface-border rounded-xl overflow-hidden']) }}>
    <table class="w-full text-sm text-left">
        @if (count($headers))
            <thead class="bg-surface text-text-secondary">
                <tr>
                    @foreach ($headers as $header)
                        <th class="px-4 py-3 font-medium">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="divide-y divide-surface-border text-text-primary">
            {{ $slot }}
        </tbody>
    </table>
</div>
