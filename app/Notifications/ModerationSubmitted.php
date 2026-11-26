<?php

namespace App\Notifications;

use App\Support\NotificationText;

class ModerationSubmitted extends BaseNotification
{
    public static function for(string $kind, string $label): self
    {
        return new self(
            NotificationText::t('submitted_title', ['kind' => NotificationText::t($kind)]),
            NotificationText::t('submitted_body', ['label' => $label]),
        );
    }
}
