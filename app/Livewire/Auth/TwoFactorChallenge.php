<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\VerifyTwoFactorChallengeAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class TwoFactorChallenge extends Component
{
    #[Validate('required|string')]
    public string $code = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->two_factor_confirmed_at) {
            $this->redirect(route('dashboard'), navigate: false);
        }
    }

    public function verify(VerifyTwoFactorChallengeAction $action): void
    {
        $this->validate();

        $user = Auth::user();

        if (! $action->execute($user, $this->code)) {
            $this->addError('code', 'O código informado é inválido.');

            return;
        }

        session()->put('2fa_passed', true);

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.two-factor-challenge');
    }
}
