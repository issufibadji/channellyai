<div>
    <h1 class="text-xl font-semibold text-text-primary mb-2">Verificação em duas etapas</h1>
    <p class="text-sm text-text-secondary mb-6">
        Informe o código do seu aplicativo autenticador, ou um dos seus códigos de recuperação.
    </p>

    <form wire:submit="verify" class="space-y-4">
        <div>
            <label for="code" class="block text-sm font-medium mb-1 text-text-primary">Código</label>
            <input
                type="text"
                id="code"
                wire:model="code"
                inputmode="numeric"
                autofocus
                autocomplete="one-time-code"
                class="w-full rounded-md bg-surface border-surface-border text-text-primary"
            >
            @error('code') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <x-button type="submit" wire:loading.attr="disabled" class="w-full">
            Verificar
        </x-button>
    </form>
</div>
