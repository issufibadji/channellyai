<div>
    <h1 class="text-xl font-semibold mb-2">Verificação em duas etapas</h1>
    <p class="text-sm text-gray-600 mb-6">
        Informe o código do seu aplicativo autenticador, ou um dos seus códigos de recuperação.
    </p>

    <form wire:submit="verify" class="space-y-4">
        <div>
            <label for="code" class="block text-sm font-medium mb-1">Código</label>
            <input
                type="text"
                id="code"
                wire:model="code"
                inputmode="numeric"
                autofocus
                autocomplete="one-time-code"
                class="w-full rounded-md border-gray-300"
            >
            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
        >
            Verificar
        </button>
    </form>
</div>
