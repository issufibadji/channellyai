<div>
    <h1 class="text-xl font-semibold mb-6">Redefinir senha</h1>

    <form wire:submit="resetPassword" class="space-y-4">
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

        <div>
            <label for="password" class="block text-sm font-medium mb-1">Nova senha</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                class="w-full rounded-md border-gray-300"
            >
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirmar nova senha</label>
            <input
                type="password"
                id="password_confirmation"
                wire:model="password_confirmation"
                class="w-full rounded-md border-gray-300"
            >
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full inline-flex justify-center items-center px-4 py-2 rounded-md font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
        >
            Redefinir senha
        </button>
    </form>
</div>
