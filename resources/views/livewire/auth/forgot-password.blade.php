<div>
    <h1 class="text-xl font-semibold text-text-primary mb-2">Recuperar senha</h1>
    <p class="text-sm text-text-secondary mb-6">Informe seu e-mail e enviaremos um link para redefinir sua senha.</p>

    @if ($status)
        <x-alert variant="success" class="mb-4">{{ $status }}</x-alert>
    @endif

    <form wire:submit="sendResetLink" class="space-y-4">
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

        <x-button type="submit" wire:loading.attr="disabled" class="w-full">
            Enviar link de recuperação
        </x-button>

        <a href="{{ route('login') }}" class="block text-center text-sm text-primary hover:underline">
            Voltar para o login
        </a>
    </form>
</div>
