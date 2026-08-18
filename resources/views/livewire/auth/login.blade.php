<div>
    <h1 class="text-xl font-semibold text-text-primary mb-6">Entrar</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium mb-1 text-text-primary">E-mail</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autofocus
                autocomplete="username"
                class="w-full rounded-md bg-surface border-surface-border text-text-primary"
            >
            @error('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1 text-text-primary">Senha</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                autocomplete="current-password"
                class="w-full rounded-md bg-surface border-surface-border text-text-primary"
            >
            @error('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="remember" class="rounded bg-surface border-surface-border">
                Lembrar-me
            </label>

            <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">
                Esqueceu a senha?
            </a>
        </div>

        <x-button type="submit" wire:loading.attr="disabled" class="w-full">
            Entrar
        </x-button>
    </form>
</div>
