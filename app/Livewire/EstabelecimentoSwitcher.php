<?php

namespace App\Livewire;

use App\Models\Estabelecimento;
use App\Services\CurrentEstabelecimento;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EstabelecimentoSwitcher extends Component
{
    public function switchTo(int $estabelecimentoId): void
    {
        $user = Auth::user();

        $podeAcessar = $user->hasRole('admin')
            ? Estabelecimento::whereKey($estabelecimentoId)->exists()
            : $user->estabelecimentos()->whereKey($estabelecimentoId)->exists();

        abort_unless($podeAcessar, 403);

        app(CurrentEstabelecimento::class)->set($estabelecimentoId);

        $this->redirect(request()->header('Referer') ?? route('dashboard'), navigate: false);
    }

    public function render()
    {
        $user = Auth::user();

        $estabelecimentos = $user->hasRole('admin')
            ? Estabelecimento::where('ativo', true)->orderBy('nome')->get()
            : $user->estabelecimentos()->where('ativo', true)->orderBy('nome')->get();

        return view('livewire.estabelecimento-switcher', [
            'estabelecimentos' => $estabelecimentos,
            'atual' => app(CurrentEstabelecimento::class)->estabelecimento(),
            'podeEscolher' => $user->hasRole('admin'),
        ]);
    }
}
