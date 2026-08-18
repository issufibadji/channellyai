<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.dashboard');
    }
}
