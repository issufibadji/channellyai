<?php

namespace App\Livewire\Admin;

use App\Models\Estabelecimento;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.master')]
class EstabelecimentoManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $estabelecimentoId = null;

    public string $nome = '';

    public string $slug = '';

    public string $tipoNegocio = 'consultorio';

    public bool $ativo = true;

    public array $usuarios = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        abort_unless(auth()->user()->can('manage-estabelecimentos'), 403);

        $this->reset(['estabelecimentoId', 'nome', 'slug', 'usuarios']);
        $this->tipoNegocio = 'consultorio';
        $this->ativo = true;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'estabelecimento-form');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('manage-estabelecimentos'), 403);

        $estabelecimento = Estabelecimento::with('usuarios')->findOrFail($id);

        $this->estabelecimentoId = $estabelecimento->id;
        $this->nome = $estabelecimento->nome;
        $this->slug = $estabelecimento->slug;
        $this->tipoNegocio = $estabelecimento->tipo_negocio;
        $this->ativo = $estabelecimento->ativo;
        $this->usuarios = $estabelecimento->usuarios->pluck('id')->all();

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'estabelecimento-form');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('manage-estabelecimentos'), 403);

        $isNew = ! $this->estabelecimentoId;

        $this->validate([
            'nome' => 'required|string|max:255',
            'slug' => [
                $isNew ? 'required' : 'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('estabelecimentos', 'slug')->ignore($this->estabelecimentoId),
            ],
            'tipoNegocio' => 'required|string|in:'.implode(',', array_keys(Estabelecimento::TIPOS_NEGOCIO)),
            'ativo' => 'boolean',
        ]);

        $estabelecimento = $isNew ? new Estabelecimento : Estabelecimento::findOrFail($this->estabelecimentoId);
        $estabelecimento->nome = $this->nome;
        $estabelecimento->tipo_negocio = $this->tipoNegocio;
        $estabelecimento->ativo = $this->ativo;

        if ($isNew) {
            $estabelecimento->slug = $this->slug;
        }

        $estabelecimento->save();
        $estabelecimento->usuarios()->sync($this->usuarios);

        $this->dispatch('close-modal');
        session()->flash('success', 'Estabelecimento salvo com sucesso.');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('manage-estabelecimentos'), 403);

        Estabelecimento::findOrFail($id)->delete();

        session()->flash('success', 'Estabelecimento removido.');
    }

    public function render()
    {
        $estabelecimentos = Estabelecimento::query()
            ->when($this->search, fn ($query) => $query->where('nome', 'like', "%{$this->search}%"))
            ->orderBy('nome')
            ->paginate(15);

        return view('livewire.admin.estabelecimento-manager', [
            'estabelecimentos' => $estabelecimentos,
            'allUsers' => User::orderBy('name')->get(),
        ]);
    }
}
