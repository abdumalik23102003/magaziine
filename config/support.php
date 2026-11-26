<?php

return [
    'visitor_cookie' => env('SUPPORT_VISITOR_COOKIE', 'visitor_token'),
    'max_length' => 1000,
    'history_limit' => 50,
    'hours' => [
        'always' => env('SUPPORT_24_7', true),
        'timezone' => env('SUPPORT_TZ', 'Asia/Tashkent'),
        'start' => '09:00',
        'end' => '21:00',
    ],
    'offline_message' => 'Operator tez orada javob beradi.',
    'polling' => [
        'open' => 3,
        'closed' => 20,
    ],
    'broadcast' => env('SUPPORT_BROADCAST', false),
];
