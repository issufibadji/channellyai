<?php

namespace App\Actions\Auth;

use App\Models\User;

class RegenerateRecoveryCodesAction
{
    public function execute(User $user): array
    {
        $codes = $this->generate();

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode($codes),
        ])->save();

        return $codes;
    }

    private function generate(): array
    {
        return collect(range(1, 8))
            ->map(fn () => strtoupper(str()->random(10)))
            ->all();
    }
}
