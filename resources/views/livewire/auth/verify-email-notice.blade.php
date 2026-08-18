<x-card class="max-w-md">
    <h1 class="text-xl font-semibold text-text-primary mb-2">Confirme seu e-mail</h1>
    <p class="text-sm text-text-secondary mb-6">
        Antes de continuar, confirme seu e-mail clicando no link que enviamos para você.
    </p>

    @if ($sent)
        <x-alert variant="success" class="mb-4">
            Um novo link de verificação foi enviado para o seu e-mail.
        </x-alert>
    @endif

    <div class="flex items-center justify-between">
        <x-button wire:click="resend" wire:loading.attr="disabled">
            Reenviar e-mail de verificação
        </x-button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-text-secondary hover:underline">Sair</button>
        </form>
    </div>
</x-card>
