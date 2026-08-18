<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\AttemptLoginAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(AttemptLoginAction $action): void
    {
        $this->validate();

        $action->execute(request(), $this->email, $this->password, $this->remember);

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
