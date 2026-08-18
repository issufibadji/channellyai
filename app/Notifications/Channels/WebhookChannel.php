<?php

namespace App\Notifications\Channels;

use App\Services\AppConfigService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class WebhookChannel
{
    public function __construct(private AppConfigService $configService) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebhook')) {
            return;
        }

        $url = $this->configService->get('webhook_url');

        if (! $url) {
            return;
        }

        Http::post($url, $notification->toWebhook($notifiable, $notification));
    }
}
