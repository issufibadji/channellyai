<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Profile;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function test_user_can_update_account_name_and_email(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', 'New Name')
            ->set('email', 'new@example.com')
            ->call('saveAccount')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('currentPassword', 'old-password')
            ->set('newPassword', 'new-secure-password')
            ->set('newPassword_confirmation', 'new-secure-password')
            ->call('saveAccount')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('currentPassword', 'wrong-password')
            ->set('newPassword', 'new-secure-password')
            ->set('newPassword_confirmation', 'new-secure-password')
            ->call('saveAccount')
            ->assertHasErrors('currentPassword');
    }

    public function test_user_can_update_additional_info(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('displayName', 'Nova Exibição')
            ->set('cpf', '123.456.789-00')
            ->set('bio', 'Minha bio')
            ->call('saveAdditionalInfo')
            ->assertHasNoErrors();

        $profile = $user->fresh()->profile;
        $this->assertSame('Nova Exibição', $profile->display_name);
        $this->assertSame('123.456.789-00', $profile->cpf);
        $this->assertSame('Minha bio', $profile->bio);
    }

    public function test_user_can_delete_account_with_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'my-password']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('deletePassword', 'my-password')
            ->call('deleteAccount');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'my-password']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('deletePassword', 'wrong-password')
            ->call('deleteAccount')
            ->assertHasErrors('deletePassword');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_can_upload_and_crop_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $dataUrl = 'data:image/png;base64,'.self::TINY_PNG_BASE64;

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('saveCroppedAvatar', $dataUrl)
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_invalid_avatar_data_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('saveCroppedAvatar', 'not-a-data-url')
            ->assertHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_user_can_manage_addresses(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('label', 'Casa')
            ->set('street', 'Rua Teste')
            ->set('city', 'São Paulo')
            ->set('state', 'sp')
            ->set('zipCode', '01000-000')
            ->set('isPrimary', true)
            ->call('saveAddress')
            ->assertHasNoErrors();

        $address = UserAddress::where('user_id', $user->id)->first();

        $this->assertSame('SP', $address->state);
        $this->assertTrue($address->is_primary);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('deleteAddress', $address->id);

        $this->assertDatabaseCount('user_addresses', 0);
    }

    public function test_user_can_add_and_remove_additional_data(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('newDataKey', 'documento')
            ->set('newDataValue', '123456')
            ->call('addAdditionalData')
            ->assertHasNoErrors();

        $data = $user->additionalData()->first();
        $this->assertSame('123456', $data->value);

        $component->call('removeAdditionalData', $data->id);

        $this->assertDatabaseCount('user_additional_data', 0);
    }
}
