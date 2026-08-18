<div>
    <h1 class="text-xl font-semibold text-text-primary mb-6">Redefinir senha</h1>

    <form wire:submit="resetPassword" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium mb-1 text-text-primary">E-mail</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autofocus
                class="w-full rounded-md bg-surface border-surface-border text-text-primary"
            >
            @error('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1 text-text-primary">Nova senha</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                class="w-full rounded-md bg-surface border-surface-border text-text-primary"
            >
            @error('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium mb-1 text-text-primary">Confirmar nova senha</label>
            <input
                type="password"
                id="password_confirmation"
                wire:model="password_confirmation"
                class="w-full rounded-md bg-surface border-surface-border text-text-primary"
            >
        </div>

        <x-button type="submit" wire:loading.attr="disabled" class="w-full">
            Redefinir senha
        </x-button>
    </form>
</div>
