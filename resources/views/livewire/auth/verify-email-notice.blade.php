<div class="max-w-md mx-auto mt-16 bg-white rounded-xl shadow-sm p-8">
    <h1 class="text-xl font-semibold mb-2">Confirme seu e-mail</h1>
    <p class="text-sm text-gray-600 mb-6">
        Antes de continuar, confirme seu e-mail clicando no link que enviamos para você.
    </p>

    @if ($sent)
        <div class="mb-4 rounded-md bg-green-50 text-green-800 text-sm px-4 py-3">
            Um novo link de verificação foi enviado para o seu e-mail.
        </div>
    @endif

    <div class="flex items-center justify-between">
        <button
            wire:click="resend"
            wire:loading.attr="disabled"
            class="inline-flex items-center px-4 py-2 rounded-md font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
        >
            Reenviar e-mail de verificação
        </button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-600 hover:underline">Sair</button>
        </form>
    </div>
</div>
