<?php

return [
    // BotFather tokeni. Mini App allaqachon shu tokenni ishlatsa, o'sha qiymatni qo'ying.
    'token' => env('TELEGRAM_BOT_TOKEN'),

    // @siz bot username (deep-link va "Botga o'tish" tugmalari uchun)
    'username' => env('TELEGRAM_BOT_USERNAME'),

    // Telegram har so'rovda X-Telegram-Bot-Api-Secret-Token sarlavhasida yuboradi.
    // Faqat A-Z a-z 0-9 _ - ; 1..256 belgi. Masalan: openssl rand -hex 32
    'webhook_secret' => env('TELEGRAM_BOT_WEBHOOK_SECRET'),

    // Bo'sh bo'lsa route('telegram.bot.webhook') ishlatiladi (APP_URL asosida).
    'webhook_url' => env('TELEGRAM_BOT_WEBHOOK_URL'),
];
