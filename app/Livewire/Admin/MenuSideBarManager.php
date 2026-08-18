<?php

namespace App\Livewire\Admin;

use App\Models\MenuSideBar;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.master')]
class MenuSideBarManager extends Component
{
    public ?int $menuId = null;

    #[Validate('required|string|max:255')]
    public string $label = '';

    #[Validate('nullable|string|max:100')]
    public string $icon = '';

    #[Validate('required|string|max:255')]
    public string $routeName = '';

    #[Validate('nullable|exists:menu_side_bars,id')]
    public ?int $parentId = null;

    #[Validate('required|integer|min:0')]
    public int $order = 0;

    public function create(): void
    {
        $this->reset(['menuId', 'label', 'icon', 'routeName', 'parentId', 'order']);
        $this->dispatch('open-modal', name: 'menu-form');
    }

    public function edit(int $id): void
    {
        $item = MenuSideBar::findOrFail($id);

        $this->menuId = $item->id;
        $this->label = $item->label;
        $this->icon = $item->icon ?? '';
        $this->routeName = $item->route_name;
        $this->parentId = $item->parent_id;
        $this->order = $item->order;

        $this->dispatch('open-modal', name: 'menu-form');
    }

    public function save(): void
    {
        $this->validate();

        if (! Route::has($this->routeName)) {
            $this->addError('routeName', 'Essa rota não existe.');

            return;
        }

        MenuSideBar::updateOrCreate(
            ['id' => $this->menuId],
            [
                'label' => $this->label,
                'icon' => $this->icon ?: null,
                'route_name' => $this->routeName,
                'parent_id' => $this->parentId,
                'order' => $this->order,
            ],
        );

        $this->dispatch('close-modal');
        session()->flash('success', 'Item de menu salvo com sucesso.');
    }

    public function delete(int $id): void
    {
        MenuSideBar::findOrFail($id)->delete();

        session()->flash('success', 'Item de menu removido.');
    }

    public function render()
    {
        return view('livewire.admin.menu-side-bar-manager', [
            'items' => MenuSideBar::roots()->with('children')->get(),
            'parentOptions' => MenuSideBar::roots()->when($this->menuId, fn ($q) => $q->where('id', '!=', $this->menuId))->get(),
        ]);
    }
}
