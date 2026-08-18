<div class="max-w-xl mx-auto mt-16 bg-white rounded-xl shadow-sm p-8">
    <h1 class="text-xl font-semibold mb-2">Verificação em duas etapas (2FA)</h1>

    @php $user = auth()->user(); @endphp

    @if ($user->two_factor_confirmed_at)
        {{-- 2FA ativo --}}
        <p class="text-sm text-green-700 mb-6">A verificação em duas etapas está ativada na sua conta.</p>

        @if ($showRecoveryCodes)
            <div class="mb-6">
                <p class="text-sm font-medium mb-2">Guarde estes códigos de recuperação em um lugar seguro:</p>
                <div class="grid grid-cols-2 gap-2 bg-gray-50 rounded-md p-4 font-mono text-sm">
                    @foreach ($this->recoveryCodes as $recoveryCode)
                        <span>{{ $recoveryCode }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex gap-3">
            <button
                wire:click="regenerateRecoveryCodes"
                wire:confirm="Isso invalida os códigos de recuperação atuais. Continuar?"
                class="px-4 py-2 rounded-md font-medium text-sm bg-gray-100 text-gray-700 hover:bg-gray-200"
            >
                Gerar novos códigos de recuperação
            </button>

            <button
                wire:click="disable"
                wire:confirm="Tem certeza que deseja desativar a verificação em duas etapas?"
                class="px-4 py-2 rounded-md font-medium text-sm bg-red-600 text-white hover:bg-red-700"
            >
                Desativar 2FA
            </button>
        </div>
    @elseif ($user->two_factor_secret)
        {{-- Aguardando confirmação --}}
        <p class="text-sm text-gray-600 mb-4">
            Escaneie o QR code abaixo com o Google Authenticator (ou outro app compatível) e informe o código gerado para confirmar.
        </p>

        <div class="mb-4 border border-gray-200 rounded-md p-4 inline-block">
            {!! $this->qrCodeSvg !!}
        </div>

        <form wire:submit="confirm" class="space-y-4 max-w-xs">
            <div>
                <label for="code" class="block text-sm font-medium mb-1">Código de confirmação</label>
                <input
                    type="text"
                    id="code"
                    wire:model="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="w-full rounded-md border-gray-300"
                >
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 rounded-md font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
            >
                Confirmar
            </button>
        </form>
    @else
        {{-- 2FA desativado --}}
        <p class="text-sm text-gray-600 mb-6">A verificação em duas etapas está desativada na sua conta.</p>

        <button
            wire:click="enable"
            class="inline-flex items-center px-4 py-2 rounded-md font-medium text-sm bg-blue-600 text-white hover:bg-blue-700"
        >
            Habilitar 2FA
        </button>
    @endif
</div>
