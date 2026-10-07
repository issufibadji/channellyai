<?php

namespace App\Services;

use App\Models\Estabelecimento;

class CurrentEstabelecimento
{
    public function id(): ?int
    {
        return session('current_estabelecimento_id');
    }

    public function set(int $estabelecimentoId): void
    {
        session(['current_estabelecimento_id' => $estabelecimentoId]);
    }

    public function clear(): void
    {
        session()->forget('current_estabelecimento_id');
    }

    public function estabelecimento(): ?Estabelecimento
    {
        $id = $this->id();

        return $id ? Estabelecimento::find($id) : null;
    }
}
