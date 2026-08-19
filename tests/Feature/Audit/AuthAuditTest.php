<?php

namespace Tests\Feature\Audit;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class AuthAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_audited(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login');

        $this->assertDatabaseHas('audits', [
            'event' => 'login',
            'user_id' => $user->id,
            'auditable_id' => $user->id,
            'auditable_type' => User::class,
        ]);
    }

    public function test_logout_is_audited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'));

        $this->assertDatabaseHas('audits', [
            'event' => 'logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_failed_login_with_valid_email_is_audited(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertDatabaseHas('audits', [
            'event' => 'login_failed',
            'auditable_id' => $user->id,
            'auditable_type' => User::class,
        ]);
    }

    public function test_failed_login_with_unknown_email_is_not_audited(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'nobody@example.com')
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertSame(0, Audit::where('event', 'login_failed')->count());
    }
}
