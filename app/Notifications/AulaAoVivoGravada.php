<?php

namespace App\Notifications;

use App\Models\AulaAoVivo;
use Illuminate\Notifications\Notification;

class AulaAoVivoGravada extends Notification
{
    public function __construct(private AulaAoVivo $aula) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Gravação da aula disponível',
            'message' => "{$this->aula->titulo} — {$this->aula->turma->nome}. Assista pela página Aulas ao Vivo.",
        ];
    }
}
