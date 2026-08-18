<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Vínculo Papéis/Usuários</h1>

    <x-table :headers="['Usuário', 'E-mail', 'Papéis', 'Atribuir papel']">
        @foreach ($users as $user)
            <tr>
                <td class="px-4 py-3">{{ $user->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $user->email }}</td>
                <td class="px-4 py-3">
                    @forelse ($user->roles as $role)
                        <x-badge>
                            {{ $role->name }}
                            <button wire:click="remove({{ $user->id }}, '{{ $role->name }}')" class="ml-1 opacity-70 hover:opacity-100">&times;</button>
                        </x-badge>
                    @empty
                        <span class="text-text-secondary">Sem papéis</span>
                    @endforelse
                </td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        <select wire:model="selectedRole.{{ $user->id }}" class="rounded-md bg-surface border-surface-border text-text-primary text-sm">
                            <option value="">Selecione um papel</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <x-button wire:click="assign({{ $user->id }})" class="text-sm">Atribuir</x-button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>
</div>
