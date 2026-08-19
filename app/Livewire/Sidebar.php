<?php

namespace App\Livewire;

use App\Models\MenuSideBar;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    #[On('menu-updated')]
    public function refresh(): void
    {
        // Re-render picks up the latest menu_side_bars state.
    }

    public function render()
    {
        $items = MenuSideBar::roots()->with('children')->get()->filter(fn ($item) => $this->visible($item));

        $items->each(function (MenuSideBar $item) {
            $item->setRelation('children', $item->children->filter(fn ($child) => $this->visible($child)));
        });

        return view('livewire.sidebar', ['groups' => $items->groupBy(fn ($item) => $item->group ?: '')]);
    }

    private function visible(MenuSideBar $item): bool
    {
        return ! $item->permission || Auth::user()?->can($item->permission);
    }
}
