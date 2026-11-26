<?php

return [
    'ttl' => 60,

    // Support chat jadvallari (6-faza). Nomlar sizdagidan farq qilsa, shu yerda moslang.
    'support' => [
        'conversations' => 'support_conversations',
        'messages' => 'support_messages',
        'message_fk' => 'conversation_id',
        'sender_column' => 'sender_type',
        'customer' => 'customer',
        'operator' => 'operator',
        'status_column' => 'status',
        'open_statuses' => ['open', 'assigned'],
        'unassigned_status' => 'open',
        'assignee_column' => 'operator_id',
        'window' => 200,
    ],
];
