<?php

namespace App\Notifications;

use App\Support\NotificationText;

class ConversationOpened extends BaseNotification
{
    public static function make(): self
    {
        return new self(NotificationText::t('conversation_title'), NotificationText::t('conversation_body'));
    }
}
