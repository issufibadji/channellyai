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
        return view('livewire.dashboard', [
            'recentNotifications' => Auth::user()->notifications()->latest()->limit(5)->get(),
        ]);
    }
}
