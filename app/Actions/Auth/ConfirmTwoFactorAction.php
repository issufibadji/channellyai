<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class ConfirmTwoFactorAction
{
    public function __construct(private Google2FA $google2fa) {}

    public function execute(User $user, string $code): void
    {
        if (! $user->two_factor_secret || ! $this->google2fa->verifyKey($user->two_factor_secret, $code)) {
            throw ValidationException::withMessages([
                'code' => 'O código informado é inválido.',
            ]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();
    }
}
