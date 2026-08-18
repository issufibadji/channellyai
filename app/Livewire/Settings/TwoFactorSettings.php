<?php

namespace App\Livewire\Settings;

use App\Actions\Auth\ConfirmTwoFactorAction;
use App\Actions\Auth\DisableTwoFactorAction;
use App\Actions\Auth\EnableTwoFactorAction;
use App\Actions\Auth\RegenerateRecoveryCodesAction;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

#[Layout('components.layouts.master')]
class TwoFactorSettings extends Component
{
    #[Validate('required|string')]
    public string $code = '';

    public bool $showRecoveryCodes = false;

    public function enable(EnableTwoFactorAction $action): void
    {
        $action->execute(Auth::user());
    }

    public function confirm(ConfirmTwoFactorAction $action): void
    {
        $this->validate();

        $action->execute(Auth::user(), $this->code);

        $this->code = '';
        $this->showRecoveryCodes = true;
    }

    public function disable(DisableTwoFactorAction $action): void
    {
        $action->execute(Auth::user());

        $this->showRecoveryCodes = false;
    }

    public function regenerateRecoveryCodes(RegenerateRecoveryCodesAction $action): void
    {
        $action->execute(Auth::user());

        $this->showRecoveryCodes = true;
    }

    public function getQrCodeSvgProperty(): ?string
    {
        $user = Auth::user();

        if (! $user->two_factor_secret || $user->two_factor_confirmed_at) {
            return null;
        }

        $otpauthUrl = (new Google2FA())->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->two_factor_secret,
        );

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($otpauthUrl);
    }

    public function getRecoveryCodesProperty(): array
    {
        $codes = Auth::user()->two_factor_recovery_codes;

        return $codes ? json_decode($codes, true) : [];
    }

    public function render()
    {
        return view('livewire.settings.two-factor-settings');
    }
}
