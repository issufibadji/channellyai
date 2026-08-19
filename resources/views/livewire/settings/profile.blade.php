<div class="max-w-3xl space-y-6">
    <div class="flex items-center gap-3 mb-2">
        <div class="w-10 h-10 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30">
            <x-heroicon-s-user-circle class="w-5 h-5 text-white" />
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">Meu Perfil</h1>
            <p class="text-sm text-text-secondary">Atualize os dados da sua conta e gerencie a autenticação em duas etapas.</p>
        </div>
    </div>

    {{-- Cabeçalho: avatar + identidade --}}
    <div x-data="avatarCropper()">
    <x-card>
        <div class="flex items-center gap-6">
            <div class="w-16 h-16 rounded-full overflow-hidden bg-primary/15 flex items-center justify-center shrink-0">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" class="w-full h-full object-cover" alt="Avatar">
                @else
                    <span class="text-xl font-semibold text-accent">{{ strtoupper(substr($name ?: 'U', 0, 1)) }}</span>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-base font-semibold text-text-primary">{{ $name }}</p>
                <p class="text-sm text-text-secondary">{{ $email }}</p>
                <p class="text-xs text-text-secondary mt-0.5">Perfil atual: <span class="text-accent capitalize">{{ $roleName }}</span></p>
            </div>

            <div class="space-y-2 shrink-0">
                <input type="file" accept="image/*" x-ref="fileInput" @change="onFileSelected($event)" class="hidden" id="avatar-input">
                <div class="flex gap-3">
                    <x-button type="button" variant="secondary" @click="$refs.fileInput.click()">Alterar foto</x-button>
                    @if ($avatarUrl)
                        <x-button type="button" variant="danger" wire:click="removeAvatar" wire:confirm="Remover o avatar atual?">Remover</x-button>
                    @endif
                </div>
                @error('avatar') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-card>

        <div
            x-show="active"
            x-cloak
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-black/60" @click="cancel"></div>

            <div class="relative bg-surface-card border border-surface-border rounded-xl p-6 w-full max-w-sm">
                <h3 class="text-text-primary font-semibold mb-4">Ajustar avatar</h3>

                <canvas
                    x-ref="canvas"
                    class="mx-auto rounded-full cursor-move touch-none bg-surface"
                    @mousedown="startDrag"
                    @mousemove.window="onDrag"
                    @mouseup.window="stopDrag"
                    @touchstart="startDrag"
                    @touchmove.window="onDrag"
                    @touchend.window="stopDrag"
                ></canvas>

                <input type="range" min="1" max="3" step="0.05" x-model.number="zoom" @input="onZoom" class="w-full mt-4">

                <div class="flex justify-end gap-3 mt-4">
                    <x-button type="button" variant="secondary" @click="cancel">Cancelar</x-button>
                    <x-button type="button" @click="confirm">Salvar avatar</x-button>
                </div>
            </div>
        </div>
    </div>

    {{-- Dados da conta (expansível) --}}
    <div x-data="{ open: false }" class="glow-border bg-surface-card/80 backdrop-blur border border-surface-border rounded-2xl overflow-hidden">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left">
            <div class="flex items-center gap-3">
                <x-heroicon-o-user class="w-5 h-5 text-accent" />
                <div>
                    <p class="text-text-primary font-semibold">Dados da conta</p>
                    <p class="text-xs text-text-secondary">{{ $name }} · {{ $email }}</p>
                </div>
            </div>
            <x-heroicon-o-chevron-down class="w-5 h-5 text-text-secondary transition-transform" x-bind:class="{ 'rotate-180': open }" />
        </button>

        <div x-show="open" x-cloak x-transition class="px-6 pb-6 space-y-6 border-t border-surface-border pt-6">
            {{-- Dados da sua conta --}}
            <div>
                <h3 class="text-sm font-semibold text-text-primary mb-3">Dados da sua conta</h3>
                <form wire:submit="saveAccount" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                            <input type="text" wire:model="name" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Email</label>
                            <input type="email" wire:model="email" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Senha atual</label>
                            <input type="password" wire:model="currentPassword" autocomplete="current-password" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('currentPassword') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Nova senha</label>
                            <input type="password" wire:model="newPassword" autocomplete="new-password" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('newPassword') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Confirmar senha</label>
                            <input type="password" wire:model="newPassword_confirmation" autocomplete="new-password" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <x-button type="submit">Salvar alterações</x-button>

                        <button
                            type="button"
                            x-data
                            @click="$dispatch('open-modal', { name: 'delete-account' })"
                            class="text-sm text-danger hover:underline"
                        >
                            Excluir conta
                        </button>
                    </div>
                </form>
            </div>

            {{-- Dados adicionais --}}
            <div class="border-t border-surface-border pt-6">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Dados adicionais</h3>
                <form wire:submit="saveAdditionalInfo" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">CPF</label>
                            <input type="text" wire:model="cpf" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('cpf') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">RG</label>
                            <input type="text" wire:model="rg" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('rg') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Data de nascimento</label>
                            <input type="date" wire:model="birthDate" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('birthDate') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Nome de exibição</label>
                            <input type="text" wire:model="displayName" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('displayName') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Telefone principal</label>
                            <input type="text" wire:model="phone" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('phone') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-text-primary">Telefone secundário</label>
                            <input type="text" wire:model="phoneSecondary" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                            @error('phoneSecondary') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-text-primary">Bio</label>
                        <textarea wire:model="bio" rows="3" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                        @error('bio') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end">
                        <x-button type="submit">Salvar dados</x-button>
                    </div>
                </form>
            </div>

            {{-- Endereços --}}
            <div class="border-t border-surface-border pt-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-text-primary">Endereços</h3>
                    <x-button wire:click="createAddress">+ Novo endereço</x-button>
                </div>

                @if ($addresses->isEmpty())
                    <p class="text-sm text-text-secondary">Nenhum endereço cadastrado.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($addresses as $address)
                            <div class="flex items-start justify-between gap-4 p-4 rounded-xl bg-surface border border-surface-border">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-text-primary">{{ $address->label }}</p>
                                        @if ($address->is_primary)
                                            <x-badge variant="primary">Principal</x-badge>
                                        @endif
                                    </div>
                                    <p class="text-sm text-text-secondary mt-1">
                                        {{ $address->street }}{{ $address->number ? ', '.$address->number : '' }}
                                        @if ($address->complement) — {{ $address->complement }} @endif
                                    </p>
                                    <p class="text-xs text-text-secondary">
                                        {{ $address->neighborhood ? $address->neighborhood.' · ' : '' }}{{ $address->city }}/{{ $address->state }} · {{ $address->zip_code }}
                                    </p>
                                </div>

                                <div class="flex gap-3 shrink-0">
                                    <button wire:click="editAddress({{ $address->id }})" class="text-primary hover:underline text-sm">Editar</button>
                                    <button wire:click="deleteAddress({{ $address->id }})" wire:confirm="Remover este endereço?" class="text-danger hover:underline text-sm">Excluir</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Campos personalizados --}}
            <div class="border-t border-surface-border pt-6">
                <h3 class="text-sm font-semibold text-text-primary mb-3">Campos personalizados</h3>

                @if ($additionalData->isNotEmpty())
                    <div class="space-y-2 mb-4">
                        @foreach ($additionalData as $data)
                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-surface border border-surface-border">
                                <div>
                                    <span class="text-sm font-medium text-text-primary">{{ $data->key }}</span>
                                    <span class="text-sm text-text-secondary ml-2">{{ $data->value }}</span>
                                </div>
                                <button wire:click="removeAdditionalData({{ $data->id }})" class="text-danger hover:underline text-xs">Remover</button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form wire:submit="addAdditionalData" class="flex gap-3 items-start">
                    <div class="flex-1">
                        <input type="text" wire:model="newDataKey" placeholder="Chave" class="w-full rounded-md bg-surface border-surface-border text-text-primary text-sm">
                        @error('newDataKey') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1">
                        <input type="text" wire:model="newDataValue" placeholder="Valor" class="w-full rounded-md bg-surface border-surface-border text-text-primary text-sm">
                        @error('newDataValue') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <x-button type="submit" variant="secondary">Adicionar</x-button>
                </form>
            </div>
        </div>
    </div>

    {{-- Segurança (expansível) --}}
    <div x-data="{ open: false }" class="glow-border bg-surface-card/80 backdrop-blur border border-surface-border rounded-2xl overflow-hidden">
        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-6 py-4 text-left">
            <div class="flex items-center gap-3">
                <x-heroicon-o-key class="w-5 h-5 text-accent" />
                <div>
                    <p class="text-text-primary font-semibold">Segurança</p>
                    <p class="text-xs text-text-secondary">Configure a autenticação em duas etapas</p>
                </div>
            </div>
            <x-heroicon-o-chevron-down class="w-5 h-5 text-text-secondary transition-transform" x-bind:class="{ 'rotate-180': open }" />
        </button>

        <div x-show="open" x-cloak x-transition class="px-6 pb-6 border-t border-surface-border pt-6">
            <livewire:settings.two-factor-settings />
        </div>
    </div>

    <x-modal name="address-form" title="Endereço">
        <form wire:submit="saveAddress" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Rótulo</label>
                    <input type="text" wire:model="label" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('label') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-text-secondary mt-6">
                    <input type="checkbox" wire:model="isPrimary" class="rounded bg-surface border-surface-border">
                    Definir como principal
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Logradouro</label>
                <input type="text" wire:model="street" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('street') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Número</label>
                    <input type="text" wire:model="number" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Complemento</label>
                    <input type="text" wire:model="complement" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Bairro</label>
                <input type="text" wire:model="neighborhood" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium mb-1 text-text-primary">Cidade</label>
                    <input type="text" wire:model="city" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('city') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">UF</label>
                    <input type="text" wire:model="state" maxlength="2" class="w-full rounded-md bg-surface border-surface-border text-text-primary uppercase">
                    @error('state') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">CEP</label>
                    <input type="text" wire:model="zipCode" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('zipCode') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">País</label>
                <input type="text" wire:model="country" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('country') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="delete-account" title="Excluir conta">
        <p class="text-sm text-text-secondary mb-4">
            Essa ação é permanente e remove todos os seus dados do sistema. Digite sua senha atual para confirmar.
        </p>

        <form wire:submit="deleteAccount" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Senha</label>
                <input type="password" wire:model="deletePassword" autocomplete="current-password" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('deletePassword') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit" variant="danger">Excluir permanentemente</x-button>
            </div>
        </form>
    </x-modal>
</div>
