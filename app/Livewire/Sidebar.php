<?php

namespace App\Livewire;

use App\Models\MenuSideBar;
use App\Services\CurrentEstabelecimento;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    private const GRUPO_ATENDIMENTO = 'Atendimento';

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

        $nomeEstabelecimento = app(CurrentEstabelecimento::class)->estabelecimento()?->nome;

        return view('livewire.sidebar', [
            'groups' => $items->groupBy(function (MenuSideBar $item) use ($nomeEstabelecimento) {
                if ($item->group === self::GRUPO_ATENDIMENTO && $nomeEstabelecimento) {
                    return $nomeEstabelecimento;
                }

                return $item->group ?: '';
            }),
        ]);
    }

    private function visible(MenuSideBar $item): bool
    {
        return ! $item->permission || Auth::user()?->can($item->permission);
    }
}
