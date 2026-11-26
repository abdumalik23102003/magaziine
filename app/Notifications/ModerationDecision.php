<?php

namespace App\Notifications;

use App\Support\NotificationText;
use Illuminate\Notifications\Messages\MailMessage;

class ModerationDecision extends BaseNotification
{
    public ?string $reason;

    public bool $approved;

    public function __construct(string $kind, string $label, bool $approved, ?string $reason = null)
    {
        $state = $approved ? 'approved' : 'rejected';
        $body = NotificationText::t($state.'_body', ['label' => $label]);

        if (! $approved && $reason) {
            $body .= ' '.NotificationText::t('reason', ['reason' => $reason]);
        }

        parent::__construct(NotificationText::t($state.'_title', ['kind' => ucfirst(NotificationText::t($kind))]), $body);

        $this->approved = $approved;
        $this->reason = $approved ? null : $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->markdown('mail.moderation-decision', [
            'title' => $this->title,
            'body' => $this->body,
        ]);
    }
}
