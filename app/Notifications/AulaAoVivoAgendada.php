<?php

namespace App\Notifications;

use App\Models\AulaAoVivo;
use Illuminate\Notifications\Notification;

class AulaAoVivoAgendada extends Notification
{
    public function __construct(private AulaAoVivo $aula) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Nova aula ao vivo agendada',
            'message' => "{$this->aula->titulo} — {$this->aula->turma->nome}, ".$this->aula->inicio_em->format('d/m/Y \à\s H:i').'.',
        ];
    }
}
