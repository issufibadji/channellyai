<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        return view('livewire.dashboard', [
            'recentNotifications' => $user->notifications()->latest()->limit(5)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'roleName' => $user->getRoleNames()->first() ?? 'Sem função',
            'memberSince' => $user->created_at,
        ]);
    }
}
