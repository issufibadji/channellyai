<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\SystemAnnouncement;
use App\Models\User;
use App\Notifications\SystemAnnouncementNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.master')]
class AnnouncementManager extends Component
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string')]
    public string $message = '';

    public string $target = 'all';

    #[Validate('required|array|min:1')]
    public array $channels = ['database'];

    public function send(): void
    {
        $this->validate();

        $users = $this->target === 'all'
            ? User::all()
            : User::role(str($this->target)->after('role:')->toString())->get();

        Notification::send($users, new SystemAnnouncementNotification($this->title, $this->message, $this->channels));

        SystemAnnouncement::create([
            'title' => $this->title,
            'message' => $this->message,
            'target' => $this->target,
            'channels' => $this->channels,
            'sent_by' => Auth::id(),
        ]);

        $this->reset(['title', 'message', 'channels']);
        $this->target = 'all';

        session()->flash('success', 'Anúncio enviado com sucesso.');
    }

    public function delete(int $id): void
    {
        SystemAnnouncement::findOrFail($id)->delete();

        session()->flash('success', 'Registro removido.');
    }

    public function render()
    {
        return view('livewire.admin.announcement-manager', [
            'announcements' => SystemAnnouncement::with('sentBy')->latest()->paginate(10),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
