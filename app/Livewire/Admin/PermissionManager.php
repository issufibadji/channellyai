<?php

namespace App\Livewire\Admin;

use App\Models\Permission;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class PermissionManager extends Component
{
    public ?int $permissionId = null;

    public string $name = '';

    public string $tag = '';

    public function create(): void
    {
        $this->reset(['permissionId', 'name', 'tag']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'permission-form');
    }

    public function edit(int $id): void
    {
        $permission = Permission::findOrFail($id);

        $this->permissionId = $permission->id;
        $this->name = $permission->name;
        $this->tag = $permission->tag ?? '';

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'permission-form');
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($this->permissionId)],
            'tag' => 'nullable|string|max:255',
        ]);

        Permission::updateOrCreate(
            ['id' => $this->permissionId],
            ['name' => $this->name, 'tag' => $this->tag ?: null],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Permissão salva com sucesso.');
    }

    public function delete(int $id): void
    {
        Permission::findOrFail($id)->delete();

        session()->flash('success', 'Permissão removida.');
    }

    public function render()
    {
        return view('livewire.admin.permission-manager', [
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }
}
