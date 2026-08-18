<x-card class="max-w-xl">
    <h1 class="text-xl font-semibold text-text-primary mb-2">Verificação em duas etapas (2FA)</h1>

    @php $user = auth()->user(); @endphp

    @if ($user->two_factor_confirmed_at)
        {{-- 2FA ativo --}}
        <p class="text-sm text-success mb-6">A verificação em duas etapas está ativada na sua conta.</p>

        @if ($showRecoveryCodes)
            <div class="mb-6">
                <p class="text-sm font-medium text-text-primary mb-2">Guarde estes códigos de recuperação em um lugar seguro:</p>
                <div class="grid grid-cols-2 gap-2 bg-surface rounded-md p-4 font-mono text-sm text-text-primary">
                    @foreach ($this->recoveryCodes as $recoveryCode)
                        <span>{{ $recoveryCode }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex gap-3">
            <x-button
                variant="secondary"
                wire:click="regenerateRecoveryCodes"
                wire:confirm="Isso invalida os códigos de recuperação atuais. Continuar?"
            >
                Gerar novos códigos de recuperação
            </x-button>

            <x-button
                variant="danger"
                wire:click="disable"
                wire:confirm="Tem certeza que deseja desativar a verificação em duas etapas?"
            >
                Desativar 2FA
            </x-button>
        </div>
    @elseif ($user->two_factor_secret)
        {{-- Aguardando confirmação --}}
        <p class="text-sm text-text-secondary mb-4">
            Escaneie o QR code abaixo com o Google Authenticator (ou outro app compatível) e informe o código gerado para confirmar.
        </p>

        <div class="mb-4 border border-surface-border rounded-md p-4 inline-block bg-white">
            {!! $this->qrCodeSvg !!}
        </div>

        <form wire:submit="confirm" class="space-y-4 max-w-xs">
            <div>
                <label for="code" class="block text-sm font-medium mb-1 text-text-primary">Código de confirmação</label>
                <input
                    type="text"
                    id="code"
                    wire:model="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    class="w-full rounded-md bg-surface border-surface-border text-text-primary"
                >
                @error('code') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <x-button type="submit" wire:loading.attr="disabled">
                Confirmar
            </x-button>
        </form>
    @else
        {{-- 2FA desativado --}}
        <p class="text-sm text-text-secondary mb-6">A verificação em duas etapas está desativada na sua conta.</p>

        <x-button wire:click="enable">
            Habilitar 2FA
        </x-button>
    @endif
</x-card>
