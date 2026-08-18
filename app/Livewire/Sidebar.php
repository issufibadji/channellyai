<?php

namespace App\Livewire;

use App\Models\MenuSideBar;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Sidebar extends Component
{
    public function render()
    {
        $items = MenuSideBar::roots()->with('children')->get()->filter(fn ($item) => $this->visible($item));

        $items->each(function (MenuSideBar $item) {
            $item->setRelation('children', $item->children->filter(fn ($child) => $this->visible($child)));
        });

        return view('livewire.sidebar', ['items' => $items]);
    }

    private function visible(MenuSideBar $item): bool
    {
        return ! $item->permission || Auth::user()?->can($item->permission);
    }
}
