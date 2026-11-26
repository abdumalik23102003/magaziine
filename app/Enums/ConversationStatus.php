<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case Closed = 'closed';
}
