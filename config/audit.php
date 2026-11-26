<?php

return [
    // Kalit shu so'zlardan birini o'zida saqlasa, old/new ga umuman yozilmaydi.
    'masked' => ['password', 'remember_token', 'token', 'secret', 'phone', 'address', 'ship_', 'latitude', 'longitude'],

    // Mail va bildirishnoma matni tili: en yoki uz.
    'mail_locale' => env('AUDIT_MAIL_LOCALE', 'en'),

    // Support chat suhbati modeli (6-faza). Bo'lmasa suhbat hodisalari o'tkazib yuboriladi.
    'conversation_model' => 'App\\Models\\SupportConversation',
    'transfer_attributes' => ['operator_id'],
];
