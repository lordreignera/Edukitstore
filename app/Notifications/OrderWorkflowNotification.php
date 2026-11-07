<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Persist an order lifecycle event against one user's Laravel notification inbox. */
class OrderWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $title,
        private readonly string $body,
        private readonly string $reference,
        private readonly string $url,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'body' => $this->body,
            'reference' => $this->reference,
            'url' => $this->url,
        ];
    }
}
