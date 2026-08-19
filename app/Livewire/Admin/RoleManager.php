<?php

namespace App\Livewire\Admin;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class RoleManager extends Component
{
    public ?int $roleId = null;

    public string $name = '';

    public array $permissions = [];

    public function create(): void
    {
        $this->reset(['roleId', 'name', 'permissions']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'role-form');
    }

    public function edit(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->all();

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'role-form');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->roleId)],
        ]);

        $role = $this->roleId
            ? Role::findOrFail($this->roleId)
            : Role::create(['name' => $this->name]);

        if ($this->roleId) {
            $role->update(['name' => $this->name]);
        }

        $role->syncPermissions($this->permissions);

        $this->dispatch('close-modal');
        session()->flash('success', 'Papel salvo com sucesso.');
    }

    public function delete(int $id): void
    {
        Role::findOrFail($id)->delete();

        session()->flash('success', 'Papel removido.');
    }

    public function render()
    {
        return view('livewire.admin.role-manager', [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
            'allPermissions' => Permission::orderBy('name')->get(),
        ]);
    }
}
