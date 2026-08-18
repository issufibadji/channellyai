<div>
    <h1 class="text-xl font-semibold mb-2">Recuperar senha</h1>
    <p class="text-sm text-gray-600 mb-6">Informe seu e-mail e enviaremos um link para redefinir sua senha.</p>

    @if ($status)
        <div class="mb-4 rounded-md bg-green-50 text-green-800 text-sm px-4 py-3">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium mb-1">E-mail</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autofocus
                class="w-full rounded-md border-gray-300"
            >
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
        >
            Enviar link de recuperação
        </button>

        <a href="{{ route('login') }}" class="block text-center text-sm text-blue-600 hover:underline">
            Voltar para o login
        </a>
    </form>
</div>
