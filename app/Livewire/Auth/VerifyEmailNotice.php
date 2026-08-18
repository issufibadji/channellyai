<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class VerifyEmailNotice extends Component
{
    public bool $sent = false;

    public function resend(): void
    {
        Auth::user()->sendEmailVerificationNotification();

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.verify-email-notice');
    }
}
