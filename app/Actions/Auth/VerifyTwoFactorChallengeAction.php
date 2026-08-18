<?php

namespace App\Actions\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class VerifyTwoFactorChallengeAction
{
    public function __construct(private Google2FA $google2fa) {}

    public function execute(User $user, string $code): bool
    {
        if ($user->two_factor_secret && $this->google2fa->verifyKey($user->two_factor_secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = json_decode($user->two_factor_recovery_codes ?? '[]', true) ?: [];
        $code = strtoupper(trim($code));

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode(array_values(array_diff($codes, [$code]))),
        ])->save();

        return true;
    }
}
