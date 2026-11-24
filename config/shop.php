<?php

return [
    // Shu summadan yuqori buyurtmada yetkazib berish bepul (USD)
    'free_delivery_from' => (float) env('SHOP_FREE_DELIVERY_FROM', 35),

    // 0.05 = 5%. 0 bo'lsa savatda soliq qatori ko'rinmaydi.
    'tax_rate' => (float) env('SHOP_TAX_RATE', 0),

    'delivery_methods' => [
        'express' => [
            'name' => 'Express (30-45 min)',
            'tag' => 'Priority',
            'text' => 'Direct dispatch within 30-45 minutes in a climate-sealed vehicle.',
            'fee' => 3.99,
        ],
        'evening' => [
            'name' => 'Evening slot',
            'tag' => 'Batch',
            'text' => 'Tonight between 7:00 PM and 9:00 PM.',
            'fee' => 1.99,
        ],
        'morning' => [
            'name' => 'Tomorrow morning',
            'tag' => 'Dawn',
            'text' => 'Tomorrow between 8:00 AM and 10:00 AM, freshly picked.',
            'fee' => 0.0,
        ],
    ],

    'payment_methods' => [
        'cod' => [
            'name' => 'Cash on delivery',
            'text' => 'Pay the courier in cash when the order arrives.',
            'icon' => 'bi-cash-coin',
        ],
        'card' => [
            'name' => 'Card to courier',
            'text' => 'Pay by card on the courier terminal.',
            'icon' => 'bi-credit-card-2-front',
        ],
    ],
];
