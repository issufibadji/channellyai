<div>
    <h1 class="text-xl font-semibold mb-6">Entrar</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium mb-1">E-mail</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autofocus
                autocomplete="username"
                class="w-full rounded-md border-gray-300"
            >
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1">Senha</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                autocomplete="current-password"
                class="w-full rounded-md border-gray-300"
            >
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300">
                Lembrar-me
            </label>

            <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">
                Esqueceu a senha?
            </a>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
        >
            Entrar
        </button>
    </form>
</div>
