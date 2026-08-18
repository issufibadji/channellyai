<?php

namespace App\Notifications\Messages;

class WebPushMessage
{
    public string $title = '';

    public string $body = '';

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
