<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('components.layouts.master')]
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $verifiedFilter = '';

    public string $roleFilter = '';

    public ?int $userId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public array $roles = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVerifiedFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        abort_unless(auth()->user()->can('create-users'), 403);

        $this->reset(['userId', 'name', 'email', 'password', 'roles']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'user-form');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('edit-users'), 403);

        $user = User::with('roles')->findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->roles = $user->roles->pluck('name')->all();

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'user-form');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can($this->userId ? 'edit-users' : 'create-users'), 403);

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => $this->userId ? 'nullable|string|min:8' : 'required|string|min:8',
        ]);

        $isNew = ! $this->userId;

        $user = $isNew ? new User() : User::findOrFail($this->userId);
        $user->name = $this->name;
        $user->email = $this->email;

        if ($this->password) {
            $user->password = Hash::make($this->password);
        }

        if ($isNew) {
            $user->email_verified_at = now();
        }

        $user->save();
        $user->syncRoles($this->roles);

        if ($isNew) {
            $user->notify(new UserAccountCreated());
        }

        $this->dispatch('close-modal');
        session()->flash('success', 'Usuário salvo com sucesso.');
    }

    public function toggleActive(int $id): void
    {
        abort_unless(auth()->user()->can('edit-users'), 403);

        $user = User::findOrFail($id);
        $user->forceFill(['active' => ! $user->active])->save();
    }

    public function toggleRequires2fa(int $id): void
    {
        abort_unless(auth()->user()->can('edit-users'), 403);

        $user = User::findOrFail($id);
        $user->forceFill(['requires_2fa' => ! $user->requires_2fa])->save();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('delete-users'), 403);

        User::findOrFail($id)->delete();

        session()->flash('success', 'Usuário removido.');
    }

    public function render()
    {
        $users = User::with('roles')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== '', fn ($query) => $query->where('active', $this->statusFilter === 'active'))
            ->when($this->verifiedFilter !== '', fn ($query) => $this->verifiedFilter === 'verified'
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at'))
            ->when($this->roleFilter, fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter)))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'allRoles' => Role::orderBy('name')->get(),
        ]);
    }
}
