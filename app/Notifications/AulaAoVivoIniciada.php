<?php

namespace App\Notifications;

use App\Models\AulaAoVivo;
use Illuminate\Notifications\Notification;

class AulaAoVivoIniciada extends Notification
{
    public function __construct(private AulaAoVivo $aula) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Aula ao vivo começou',
            'message' => "{$this->aula->titulo} — {$this->aula->turma->nome}. Entre pela página Aulas ao Vivo.",
        ];
    }
}
