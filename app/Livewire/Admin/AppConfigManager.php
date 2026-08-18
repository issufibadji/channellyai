<?php

namespace App\Livewire\Admin;

use App\Models\AppConfig;
use App\Services\AppConfigService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.master')]
class AppConfigManager extends Component
{
    use WithFileUploads;

    public ?int $configId = null;

    public string $key = '';

    public string $value = '';

    public string $description = '';

    public $media = null;

    public bool $required = false;

    public function create(): void
    {
        $this->reset(['configId', 'key', 'value', 'description', 'media', 'required']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'config-form');
    }

    public function edit(int $id): void
    {
        $config = AppConfig::findOrFail($id);

        $this->configId = $config->id;
        $this->key = $config->key;
        $this->value = $config->value ?? '';
        $this->description = $config->description ?? '';
        $this->media = null;
        $this->required = $config->required;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'config-form');
    }

    public function save(AppConfigService $service): void
    {
        $this->validate([
            'key' => ['required', 'string', 'max:255', Rule::unique('app_configs', 'key')->ignore($this->configId)],
            'value' => 'nullable|string',
            'description' => 'nullable|string',
            'media' => 'nullable|image|max:2048',
            'required' => 'boolean',
        ]);

        $config = $this->configId ? AppConfig::findOrFail($this->configId) : new AppConfig();
        $previousKey = $config->key;

        $config->key = $this->key;
        $config->value = $this->value ?: null;
        $config->description = $this->description ?: null;
        $config->required = $this->required;

        if ($this->media) {
            if ($config->media_path) {
                Storage::disk('public')->delete($config->media_path);
            }

            $config->media_path = $this->media->store('app-configs', 'public');
        }

        $config->save();

        $service->forget($this->key);

        if ($previousKey && $previousKey !== $this->key) {
            $service->forget($previousKey);
        }

        $this->dispatch('close-modal');
        session()->flash('success', 'Configuração salva com sucesso.');
    }

    public function delete(int $id, AppConfigService $service): void
    {
        $config = AppConfig::findOrFail($id);

        if ($config->media_path) {
            Storage::disk('public')->delete($config->media_path);
        }

        $config->delete();
        $service->forget($config->key);

        session()->flash('success', 'Configuração removida.');
    }

    public function render()
    {
        return view('livewire.admin.app-config-manager', [
            'configs' => AppConfig::orderBy('key')->get(),
        ]);
    }
}
