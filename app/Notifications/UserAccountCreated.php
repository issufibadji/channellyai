<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class UserAccountCreated extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Bem-vindo!',
            'message' => 'Sua conta foi criada no sistema.',
        ];
    }
}
