<?php

namespace App\Notifications;

use App\Notifications\Channels\WebhookChannel;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;

class SystemAnnouncementNotification extends Notification
{
    /**
     * @param  array<int, string>  $channels  Subset of: database, webhook, webpush
     */
    public function __construct(
        private string $title,
        private string $message,
        private array $channels,
    ) {}

    public function via(object $notifiable): array
    {
        return array_map(
            fn (string $channel) => match ($channel) {
                'webhook' => WebhookChannel::class,
                'webpush' => WebPushChannel::class,
                default => $channel,
            },
            $this->channels,
        );
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage())->title($this->title)->body($this->message);
    }

    public function toWebhook(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'user_id' => $notifiable->id,
        ];
    }
}
