<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.master')]
class RoleUserLinker extends Component
{
    public array $selectedRole = [];

    public function assign(int $userId): void
    {
        $roleName = $this->selectedRole[$userId] ?? null;

        if (! $roleName) {
            return;
        }

        User::findOrFail($userId)->assignRole($roleName);

        unset($this->selectedRole[$userId]);
        session()->flash('success', 'Papel atribuído com sucesso.');
    }

    public function remove(int $userId, string $roleName): void
    {
        User::findOrFail($userId)->removeRole($roleName);

        session()->flash('success', 'Papel removido do usuário.');
    }

    public function render()
    {
        return view('livewire.admin.role-user-linker', [
            'users' => User::with('roles')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
