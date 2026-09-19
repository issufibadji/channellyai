@props(['titulo'])

<section
    x-data="{
        noInicio: true,
        noFim: false,
        checar() {
            const el = this.$refs.trilha;
            this.noInicio = el.scrollLeft <= 4;
            this.noFim = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
        },
        rolar(direcao) {
            const el = this.$refs.trilha;
            el.scrollBy({ left: direcao * el.clientWidth * 0.8, behavior: 'smooth' });
        },
    }"
    x-init="$nextTick(() => checar())"
    class="mb-10"
>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold text-text-primary">{{ $titulo }}</h2>
        <div class="flex items-center gap-1">
            <button type="button" @click="rolar(-1)" :disabled="noInicio" aria-label="Anterior" class="p-1 rounded-full text-text-secondary hover:text-text-primary disabled:opacity-30 disabled:cursor-default">
                <x-heroicon-o-chevron-left class="w-5 h-5" />
            </button>
            <button type="button" @click="rolar(1)" :disabled="noFim" aria-label="Próximo" class="p-1 rounded-full text-text-secondary hover:text-text-primary disabled:opacity-30 disabled:cursor-default">
                <x-heroicon-o-chevron-right class="w-5 h-5" />
            </button>
        </div>
    </div>

    <div
        x-ref="trilha"
        @scroll.passive="checar()"
        class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    >
        {{ $slot }}
    </div>
</section>
