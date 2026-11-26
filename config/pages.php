<?php

/*
 | Statik sahifalar matni. :phone, :email, :hours avtomatik almashtiriladi.
 | MUHIM: bu umumiy shablon. Qonuniy matnlarni (Privacy, Terms) e'lon qilishdan oldin
 | o'z mamlakatingiz qonunlariga ko'ra yurist bilan tekshiring, muddat va hududlarni o'zingizga moslang.
 */
return [
    'updated_at' => '26 November 2026',

    'support_phone' => env('SUPPORT_PHONE', '+998-90-188-6641'),
    'support_email' => env('SUPPORT_EMAIL'),

    // Refund sahifasidagi muddat (soat).
    'refund_hours' => 24,

    // Bosh sahifadagi ilova bo'limi: URL bo'sh bo'lsa, tugma "Coming soon" bo'lib turadi.
    'app_store_url' => env('APP_STORE_URL'),
    'play_store_url' => env('PLAY_STORE_URL'),

    'pages' => [
        'refund-policy' => [
            'title' => 'Refund Policy',
            'intro' => 'We want every order to arrive fresh. If something is wrong, tell us and we will make it right.',
            'sections' => [
                [
                    'heading' => 'When you can ask for a refund',
                    'items' => [
                        'An item is missing from your order.',
                        'An item arrived damaged, spoiled or not fresh.',
                        'You received a different item than the one you ordered.',
                        'Your order was not delivered.',
                    ],
                ],
                [
                    'heading' => 'How to report a problem',
                    'paragraphs' => [
                        'Contact us within :hours hours of delivery by phone at :phone or through the live chat. Please include your order number and a short description. For damaged or spoiled items, a photo helps us resolve it faster.',
                    ],
                ],
                [
                    'heading' => 'What you get',
                    'items' => [
                        'A replacement with your next delivery, or a refund for the affected items, whichever you prefer.',
                        'Cash on delivery: the refund is handed back by the courier or credited to your next order.',
                        'Card to courier: the refund goes back to the same card. Banks usually need several business days to show it.',
                    ],
                ],
                [
                    'heading' => 'Cancelling an order',
                    'paragraphs' => [
                        'While your order is still Pending, you can cancel it yourself from the order page in your profile. Once it is being prepared or is on its way, please contact support and we will do our best to help.',
                    ],
                ],
                [
                    'heading' => 'What we cannot refund',
                    'items' => [
                        'Items damaged after delivery because of storage or handling.',
                        'Opened perishable products where the reason is a change of mind.',
                        'Problems reported after the :hours-hour window.',
                    ],
                ],
            ],
        ],

        'delivery-area' => [
            'title' => 'Delivery Area',
            'intro' => 'How, where and when your groceries reach you.',
            'sections' => [
                [
                    'heading' => 'Where we deliver',
                    'paragraphs' => [
                        'We deliver to addresses inside our service zones. Set your location in the header and enter your full address at checkout. If your address is outside our zones, you will be told before you place the order.',
                    ],
                ],
                [
                    'heading' => 'Delivery windows and fees',
                    'paragraphs' => [
                        'Pick a delivery window at checkout. The available windows and any delivery fee are shown before you confirm, so there are no surprises after you order.',
                    ],
                ],
                [
                    'heading' => 'Paying on delivery',
                    'items' => [
                        'Cash on delivery: pay the courier in cash when the order arrives.',
                        'Card to courier: pay by card on the courier terminal.',
                    ],
                ],
                [
                    'heading' => 'Receiving your order',
                    'paragraphs' => [
                        'Please keep your phone nearby and check the items at the door. If the courier cannot reach you, we will call you and try again. Orders that cannot be delivered after several attempts may be cancelled.',
                    ],
                ],
                [
                    'heading' => 'Delays',
                    'paragraphs' => [
                        'Weather, traffic or high demand can sometimes delay a delivery. If your order is late, call us on :phone and we will check where it is.',
                    ],
                ],
            ],
        ],

        'privacy-policy' => [
            'title' => 'Privacy Policy',
            'intro' => 'What we collect, why we collect it and what you can do about it.',
            'sections' => [
                [
                    'heading' => 'What we collect',
                    'items' => [
                        'Account details: name, email address, phone number and delivery address.',
                        'Order history: what you bought, when and how you paid.',
                        'Content you post: ratings, comments and replies. These are public and show your name.',
                        'Approximate location, only if you allow your browser to share it. We use it to show a short place name in the header.',
                        'Technical data: a session cookie that keeps you signed in and remembers your cart.',
                    ],
                ],
                [
                    'heading' => 'How we use it',
                    'items' => [
                        'To process and deliver your orders and contact you about them.',
                        'To keep your cart, favourites and profile working.',
                        'To moderate comments and keep the community safe.',
                        'To improve the shop. We do not sell your personal data.',
                    ],
                ],
                [
                    'heading' => 'Who sees it',
                    'paragraphs' => [
                        'Only our team and the couriers who deliver your order see your name, phone and address. Our hosting and infrastructure providers process data on our behalf. A map service receives rounded coordinates when you share your location. We share information with authorities only when the law requires it.',
                    ],
                ],
                [
                    'heading' => 'How long we keep it',
                    'paragraphs' => [
                        'We keep account and order data while your account is active and as long as the law requires for accounting. Comments stay visible until you or a moderator delete them.',
                    ],
                ],
                [
                    'heading' => 'Your choices',
                    'items' => [
                        'You can edit your name, phone and address in your profile at any time.',
                        'You can delete your own comments, together with their replies.',
                        'To access, correct or delete your data, contact us at :email.',
                    ],
                ],
            ],
        ],

        'terms-of-service' => [
            'title' => 'Terms of Service',
            'intro' => 'By using FoodMart you agree to these terms. Please read them once.',
            'sections' => [
                [
                    'heading' => 'Your account',
                    'paragraphs' => [
                        'Give us accurate details and keep your password private. You are responsible for what happens under your account. We may suspend accounts that are used to cheat or abuse the shop.',
                    ],
                ],
                [
                    'heading' => 'Orders and prices',
                    'paragraphs' => [
                        'Prices and availability are shown on the site and can change. An order is confirmed when we accept it. If an item turns out to be unavailable, we will contact you and offer a replacement or a refund for that item.',
                    ],
                ],
                [
                    'heading' => 'Ratings and comments',
                    'items' => [
                        'You may rate a product or post once. Rate honestly, based on your own experience.',
                        'Do not post spam, advertising, insults, hate speech or anyone\'s private information.',
                        'We may remove comments that break these rules, together with their replies.',
                        'You keep ownership of what you write, and you allow us to display it on the site.',
                    ],
                ],
                [
                    'heading' => 'Delivery, refunds and cancellations',
                    'paragraphs' => [
                        'Delivery and refunds work as described on the Delivery Area and Refund Policy pages, which are part of these terms.',
                    ],
                ],
                [
                    'heading' => 'Liability',
                    'paragraphs' => [
                        'We take care to keep the site accurate and available, but we cannot promise it will always be error-free. To the extent the law allows, our liability is limited to the value of the affected order.',
                    ],
                ],
                [
                    'heading' => 'Changes',
                    'paragraphs' => [
                        'We may update these terms. The date at the top of this page shows when they last changed. Continuing to use the shop means you accept the update.',
                    ],
                ],
            ],
        ],

        'security' => [
            'title' => 'Security',
            'intro' => 'How we protect your account and what you can do to help.',
            'sections' => [
                [
                    'heading' => 'What we do',
                    'items' => [
                        'Passwords are stored hashed, never as plain text.',
                        'Forms are protected against cross-site request forgery.',
                        'Staff access is limited by role, and only the roles that need it can reach the admin panels.',
                        'We never store card numbers: card payments are made on the courier terminal.',
                        'Rating and comment posting is rate-limited to slow down abuse.',
                    ],
                ],
                [
                    'heading' => 'What you can do',
                    'items' => [
                        'Use a long, unique password for your account.',
                        'Do not share your password or any codes with anyone, including people who say they work for us. We never ask for your password.',
                        'Sign out on shared devices.',
                    ],
                ],
                [
                    'heading' => 'Report a vulnerability',
                    'paragraphs' => [
                        'If you find a security problem, please tell us privately at :email or call :phone, and give us a reasonable time to fix it before sharing it publicly. We appreciate responsible reports.',
                    ],
                ],
            ],
        ],
    ],
];
