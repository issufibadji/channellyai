<div x-data="{ tab: 'ios' }">
    <x-card title="Instalar como aplicativo">
        <div class="flex gap-2 mb-4 border-b border-surface-border">
            <button
                type="button"
                @click="tab = 'ios'"
                :class="tab === 'ios' ? 'border-primary text-text-primary' : 'border-transparent text-text-secondary'"
                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px"
            >
                iOS (Safari)
            </button>
            <button
                type="button"
                @click="tab = 'android'"
                :class="tab === 'android' ? 'border-primary text-text-primary' : 'border-transparent text-text-secondary'"
                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px"
            >
                Android (Chrome)
            </button>
        </div>

        <ol x-show="tab === 'ios'" class="list-decimal list-inside space-y-2 text-sm text-text-secondary">
            <li>Toque no ícone de <span class="text-text-primary font-medium">Compartilhar</span> na barra do Safari.</li>
            <li>Role o menu de opções para baixo.</li>
            <li>Toque em <span class="text-text-primary font-medium">"Adicionar à Tela de Início"</span>.</li>
            <li>Confirme tocando em <span class="text-text-primary font-medium">"Adicionar"</span>.</li>
        </ol>

        <ol x-show="tab === 'android'" x-cloak class="list-decimal list-inside space-y-2 text-sm text-text-secondary">
            <li>Toque no menu de <span class="text-text-primary font-medium">3 pontos</span> no canto superior direito do Chrome.</li>
            <li>Toque em <span class="text-text-primary font-medium">"Adicionar à tela inicial"</span>.</li>
            <li>Confirme tocando em <span class="text-text-primary font-medium">"Adicionar"</span>.</li>
        </ol>
    </x-card>
</div>
