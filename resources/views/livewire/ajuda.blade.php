<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Central de Ajuda</h1>

    @if ($isAluno)
        <div class="rounded-2xl p-6 mb-6 bg-linear-to-r from-primary to-accent text-white shadow-lg shadow-primary/25">
            <h2 class="text-xl font-semibold mb-1">Central de Ajuda</h2>
            <p class="text-sm text-white/90">{{ $banner ?? 'Aqui você encontra respostas para as dúvidas mais comuns sobre a plataforma.' }}</p>
        </div>

        <div class="space-y-3 mb-6">
            @forelse ($faqs as $faq)
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button" class="w-full text-left">
                        <x-card class="hover:border-primary/50 transition">
                            <div class="flex items-center gap-3">
                                @if ($faq->icone)
                                    <div class="w-8 h-8 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
                                        <x-icon :name="'heroicon-o-'.$faq->icone" class="w-4 h-4 text-accent" />
                                    </div>
                                @endif
                                <span class="text-text-primary font-medium">{{ $faq->pergunta }}</span>
                            </div>
                        </x-card>
                    </button>

                    <div x-show="open" x-transition class="mt-2 px-4">
                        <p class="text-sm text-text-secondary whitespace-pre-line">{{ $faq->resposta }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-text-secondary">Nenhuma pergunta frequente cadastrada ainda.</p>
            @endforelse
        </div>

        @include('livewire.partials.guia-pwa')

        <p class="text-sm text-text-secondary mt-6">Ainda tem dúvidas? Fale com seu professor ou administrador.</p>
    @else
        <x-card>
            @if ($conteudo)
                <p class="text-sm text-text-secondary whitespace-pre-line">{{ $conteudo }}</p>
            @else
                <p class="text-sm text-text-secondary">Nenhum conteúdo de ajuda cadastrado para o seu perfil ainda.</p>
            @endif
        </x-card>
    @endif
</div>
