<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use OwenIt\Auditing\Models\Audit;

#[Layout('components.layouts.master')]
class AuditManager extends Component
{
    use WithPagination;

    public ?int $viewingId = null;

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->dispatch('open-modal', name: 'audit-view');
    }

    public function delete(int $id): void
    {
        Audit::findOrFail($id)->delete();

        session()->flash('success', 'Registro de auditoria removido.');
    }

    public function getViewingProperty(): ?Audit
    {
        return $this->viewingId ? Audit::with('user')->find($this->viewingId) : null;
    }

    public function render()
    {
        return view('livewire.admin.audit-manager', [
            'audits' => Audit::with('user')->latest()->paginate(15),
        ]);
    }
}
