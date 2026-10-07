<?php

namespace App\Livewire\Atendimento;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class SemEstabelecimento extends Component
{
    public function render()
    {
        return view('livewire.atendimento.sem-estabelecimento');
    }
}
