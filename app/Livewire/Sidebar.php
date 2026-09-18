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

    /**
     * O admin tem bypass de permissão (Gate::before) e por isso enxergaria
     * qualquer item permissionado, incluindo a seção Acadêmico — que é
     * operacional (professor/aluno), não administrativa. Escondemos essa
     * seção do admin na sidebar; o acesso direto por URL continua liberado
     * pelo bypass, isso é só uma questão de poluição visual do menu.
     */
    private function visible(MenuSideBar $item): bool
    {
        if ($item->group === 'Acadêmico' && Auth::user()?->hasRole('admin')) {
            return false;
        }

        return ! $item->permission || Auth::user()?->can($item->permission);
    }
}
