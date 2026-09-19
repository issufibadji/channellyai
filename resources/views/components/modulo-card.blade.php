@props(['modulo', 'numero', 'href', 'total' => 0, 'concluidas' => 0, 'bloqueio' => null])

@php
    $bloqueado = $bloqueio !== null;
    $rotulo = $modulo->categoria === 'extra' ? $modulo->nome : 'Nível '.$modulo->nivel;
    $percentual = $total > 0 ? round($concluidas / $total * 100) : 0;
@endphp

<a
    href="{{ $bloqueado ? '#' : $href }}"
    @if ($bloqueado) aria-disabled="true" @click.prevent @endif
    title="{{ $modulo->nome }}"
    class="group relative block shrink-0 snap-start w-40 sm:w-52 aspect-[3/4] rounded-2xl overflow-hidden border border-surface-border bg-surface-card shadow-sm {{ $bloqueado ? 'cursor-not-allowed' : 'hover:border-primary/60 hover:shadow-lg transition' }}"
>
    @if ($modulo->capa_path)
        <img src="{{ asset('storage/'.$modulo->capa_path) }}" alt="{{ $modulo->nome }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
    @else
        <div class="absolute inset-0 bg-linear-to-br from-primary to-accent"></div>
        <div class="absolute inset-0 flex items-center justify-center px-3 text-center">
            <span class="font-bold text-white drop-shadow break-words {{ $modulo->categoria === 'extra' ? 'text-xl sm:text-2xl' : 'text-3xl sm:text-4xl' }}">{{ $rotulo }}</span>
        </div>
    @endif

    <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded bg-black/55 text-[11px] font-semibold text-white">{{ $numero }}</span>

    @unless ($bloqueado)
        <div class="absolute inset-0 flex flex-col items-center justify-end gap-1 pb-4 bg-black/45 opacity-0 group-hover:opacity-100 transition">
            <span class="w-11 h-11 rounded-full bg-primary flex items-center justify-center shadow-lg">
                <x-heroicon-s-play class="w-5 h-5 text-white" />
            </span>
            <span class="text-xs font-medium text-white">{{ $rotulo }}</span>
            <span class="text-[11px] text-white/80">{{ $total }} {{ $total === 1 ? 'aula' : 'aulas' }}</span>
        </div>

        @if ($concluidas > 0)
            <div class="absolute bottom-0 inset-x-0 h-1 bg-black/30">
                <div class="h-full bg-primary" style="width: {{ $percentual }}%"></div>
            </div>
        @endif
    @else
        <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/60 backdrop-blur-[2px]">
            <x-heroicon-o-lock-closed class="w-8 h-8 text-white/90" />
            <span class="text-xs text-white/90 text-center px-2">
                {{ is_int($bloqueio) ? 'Libera em '.$bloqueio.' '.($bloqueio === 1 ? 'dia' : 'dias') : 'Bloqueado' }}
            </span>
        </div>
    @endunless
</a>
