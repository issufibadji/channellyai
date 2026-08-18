<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class UserList extends Component
{
    public function render()
    {
        return view('livewire.admin.user-list', [
            'users' => User::with('roles')->orderBy('name')->get(),
        ]);
    }
}
