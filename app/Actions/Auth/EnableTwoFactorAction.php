<?php

namespace App\Actions\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class EnableTwoFactorAction
{
    public function __construct(
        private Google2FA $google2fa,
        private RegenerateRecoveryCodesAction $regenerateRecoveryCodes,
    ) {}

    public function execute(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => $this->google2fa->generateSecretKey(),
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->regenerateRecoveryCodes->execute($user);
    }
}
