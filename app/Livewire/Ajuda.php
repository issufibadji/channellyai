<?php

namespace App\Livewire;

use App\Models\Ajuda as AjudaModel;
use App\Models\AjudaFaq;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class Ajuda extends Component
{
    public function render()
    {
        $user = Auth::user();
        $isAluno = $user->hasRole('aluno');

        $data = ['isAluno' => $isAluno];

        if ($isAluno) {
            $data['banner'] = AjudaModel::where('role', 'aluno')->first()?->conteudo;
            $data['faqs'] = AjudaFaq::ativo()->paraRole('aluno')->get();
        } else {
            $role = $user->getRoleNames()->first();
            $data['conteudo'] = $role ? AjudaModel::where('role', $role)->first()?->conteudo : null;
        }

        return view('livewire.ajuda', $data);
    }
}
