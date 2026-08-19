<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public string $deletePassword = '';

    public string $displayName = '';

    public string $cpf = '';

    public string $rg = '';

    public string $bio = '';

    public string $phone = '';

    public string $phoneSecondary = '';

    public ?string $birthDate = null;

    public ?int $addressId = null;

    public string $label = 'Principal';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public string $zipCode = '';

    public string $country = 'Brasil';

    public bool $isPrimary = false;

    public string $newDataKey = '';

    public string $newDataValue = '';

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->profile;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->displayName = $profile?->display_name ?? '';
        $this->cpf = $profile?->cpf ?? '';
        $this->rg = $profile?->rg ?? '';
        $this->bio = $profile?->bio ?? '';
        $this->phone = $profile?->phone ?? '';
        $this->phoneSecondary = $profile?->phone_secondary ?? '';
        $this->birthDate = $profile?->birth_date?->format('Y-m-d');
    }

    public function saveAccount(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'currentPassword' => ['nullable', 'required_with:newPassword', 'current_password'],
            'newPassword' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $this->name;

        if ($this->email !== $user->email) {
            $user->email = $this->email;
            $user->email_verified_at = null;
        }

        if ($this->newPassword) {
            $user->password = $this->newPassword;
        }

        $user->save();

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        $this->reset(['currentPassword', 'newPassword', 'newPassword_confirmation']);

        session()->flash('success', 'Dados da conta atualizados com sucesso.');
    }

    public function deleteAccount(): void
    {
        $this->validate([
            'deletePassword' => ['required', 'current_password'],
        ]);

        $user = Auth::user();

        Auth::logout();
        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: false);
    }

    public function saveAdditionalInfo(): void
    {
        $this->validate([
            'displayName' => 'nullable|string|max:255',
            'cpf' => 'nullable|string|max:20',
            'rg' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:30',
            'phoneSecondary' => 'nullable|string|max:30',
            'birthDate' => 'nullable|date',
        ]);

        Auth::user()->profile()->updateOrCreate([], [
            'display_name' => $this->displayName ?: null,
            'cpf' => $this->cpf ?: null,
            'rg' => $this->rg ?: null,
            'bio' => $this->bio ?: null,
            'phone' => $this->phone ?: null,
            'phone_secondary' => $this->phoneSecondary ?: null,
            'birth_date' => $this->birthDate ?: null,
        ]);

        session()->flash('success', 'Dados adicionais atualizados com sucesso.');
    }

    public function saveCroppedAvatar(string $dataUrl): void
    {
        $prefix = 'data:image/png;base64,';

        if (! str_starts_with($dataUrl, $prefix)) {
            $this->addError('avatar', 'Formato de imagem inválido.');

            return;
        }

        $decoded = base64_decode(substr($dataUrl, strlen($prefix)), true);

        if ($decoded === false || strlen($decoded) > 3 * 1024 * 1024) {
            $this->addError('avatar', 'Imagem inválida ou maior que 3MB.');

            return;
        }

        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = 'avatars/'.$user->id.'-'.now()->timestamp.'.png';
        Storage::disk('public')->put($path, $decoded);

        $user->update(['avatar_path' => $path]);

        session()->flash('success', 'Avatar atualizado com sucesso.');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        session()->flash('success', 'Avatar removido.');
    }

    public function createAddress(): void
    {
        $this->reset([
            'addressId', 'street', 'number', 'complement',
            'neighborhood', 'city', 'state', 'zipCode', 'isPrimary',
        ]);
        $this->label = 'Principal';
        $this->country = 'Brasil';
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'address-form');
    }

    public function editAddress(int $id): void
    {
        $address = Auth::user()->addresses()->findOrFail($id);

        $this->addressId = $address->id;
        $this->label = $address->label;
        $this->street = $address->street;
        $this->number = $address->number ?? '';
        $this->complement = $address->complement ?? '';
        $this->neighborhood = $address->neighborhood ?? '';
        $this->city = $address->city;
        $this->state = $address->state;
        $this->zipCode = $address->zip_code;
        $this->country = $address->country;
        $this->isPrimary = $address->is_primary;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'address-form');
    }

    public function saveAddress(): void
    {
        $this->validate([
            'label' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
            'zipCode' => 'required|string|max:20',
            'country' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        if ($this->isPrimary) {
            $user->addresses()->update(['is_primary' => false]);
        }

        $user->addresses()->updateOrCreate(
            ['id' => $this->addressId],
            [
                'label' => $this->label,
                'street' => $this->street,
                'number' => $this->number ?: null,
                'complement' => $this->complement ?: null,
                'neighborhood' => $this->neighborhood ?: null,
                'city' => $this->city,
                'state' => strtoupper($this->state),
                'zip_code' => $this->zipCode,
                'country' => $this->country,
                'is_primary' => $this->isPrimary,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Endereço salvo com sucesso.');
    }

    public function deleteAddress(int $id): void
    {
        Auth::user()->addresses()->where('id', $id)->delete();

        session()->flash('success', 'Endereço removido.');
    }

    public function addAdditionalData(): void
    {
        $this->validate([
            'newDataKey' => 'required|string|max:100',
            'newDataValue' => 'nullable|string|max:1000',
        ]);

        Auth::user()->additionalData()->updateOrCreate(
            ['key' => $this->newDataKey],
            ['value' => $this->newDataValue ?: null],
        );

        $this->reset(['newDataKey', 'newDataValue']);
    }

    public function removeAdditionalData(int $id): void
    {
        Auth::user()->additionalData()->where('id', $id)->delete();
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.settings.profile', [
            'addresses' => $user->addresses()->orderByDesc('is_primary')->get(),
            'additionalData' => $user->additionalData()->orderBy('key')->get(),
            'avatarUrl' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
            'roleName' => $user->getRoleNames()->first() ?? 'Sem função',
        ]);
    }
}
