<?php

return [

    /* @BotFather bergan token. Faqat .env da saqlanadi. */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    /* Mini App ning ochiq HTTPS manzili. Bo'sh bo'lsa APP_URL + /tma. */
    'webapp_url' => env('TELEGRAM_WEBAPP_URL'),

    /* initData necha soniya yaroqli. */
    'init_data_max_age' => (int) env('TELEGRAM_INIT_DATA_MAX_AGE', 86400),

    /* Saytdagi "Telegram bilan kirish" tugmasi uchun bot username (@ siz). Bo'sh bo'lsa tugma ko'rinmaydi. */
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),

    /* Login Widget imzosi necha soniya yaroqli (initData dan qisqaroq: URL ga tushadi). */
    'widget_max_age' => (int) env('TELEGRAM_WIDGET_MAX_AGE', 600),

    /* Bot menyu tugmasidagi yozuv. */
    'menu_button_text' => env('TELEGRAM_MENU_TEXT', 'Shop'),

    /* Tunnel (cloudflared/ngrok) yoki reverse proxy orqasida: lokalda "*", serverda proxy IP si. */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
