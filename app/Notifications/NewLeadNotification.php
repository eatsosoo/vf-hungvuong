<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewLeadNotification extends Notification
{
    public function __construct(public int $leadId, public string $type) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['lead_id' => $this->leadId, 'type' => $this->type];
    }
}
