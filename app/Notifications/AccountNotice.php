<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AccountNotice extends Notification
{
    public function __construct(
        public readonly string $title,
        public readonly string $message,
        public readonly string $path,
        public readonly string $kind,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toDatabase(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'url' => $this->path, 'kind' => $this->kind];
    }
}
