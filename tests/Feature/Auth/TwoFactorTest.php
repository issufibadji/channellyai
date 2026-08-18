<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Livewire\Settings\TwoFactorSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enable_two_factor_authentication(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TwoFactorSettings::class)
            ->call('enable');

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_user_can_confirm_two_factor_with_valid_code(): void
    {
        $user = User::factory()->create();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user->forceFill(['two_factor_secret' => $secret])->save();

        Livewire::actingAs($user)
            ->test(TwoFactorSettings::class)
            ->set('code', $google2fa->getCurrentOtp($secret))
            ->call('confirm')
            ->assertHasNoErrors();

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_two_factor_challenge_is_required_after_login_when_enabled(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login');

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('two-factor.challenge'));
    }

    public function test_user_can_pass_two_factor_challenge_with_valid_code(): void
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($user);

        Livewire::test(\App\Livewire\Auth\TwoFactorChallenge::class)
            ->set('code', $google2fa->getCurrentOtp($secret))
            ->call('verify')
            ->assertRedirect(route('dashboard'));

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }
}
