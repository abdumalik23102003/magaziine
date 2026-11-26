<?php

return [
    // auth: true = faqat kirganlarga, false = faqat mehmonlarga, kalit yo'q = hammaga. event = route o'rniga JS hodisasi.
    'pages' => [
        ['label' => 'Bosh sahifa', 'route' => 'home', 'icon' => 'bi-house'],
        ['label' => 'Katalog', 'route' => 'catalog.index', 'icon' => 'bi-grid'],
        ['label' => 'Brendlar', 'route' => 'brands.index', 'icon' => 'bi-award'],
        ['label' => 'Blog', 'route' => 'blog.index', 'icon' => 'bi-journal-text'],
        ['label' => 'Istaklar', 'route' => 'wishlist.show', 'icon' => 'bi-heart'],
        ['label' => 'Savat', 'route' => 'cart.show', 'icon' => 'bi-bag'],
        ['label' => 'Buyurtmalarim', 'route' => 'profile', 'fragment' => 'orders', 'auth' => true, 'icon' => 'bi-box-seam'],
        ['label' => 'Profil', 'route' => 'profile', 'auth' => true, 'icon' => 'bi-person'],
        ['label' => 'Kirish', 'route' => 'login', 'auth' => false, 'icon' => 'bi-box-arrow-in-right'],
        ['label' => 'Ro\'yxatdan o\'tish', 'route' => 'register', 'auth' => false, 'icon' => 'bi-person-plus'],
        ['label' => 'Yordam (chat)', 'event' => 'support-chat:toggle', 'icon' => 'bi-chat-dots'],
    ],

    'featured_brands_limit' => 8,
    'latest_posts' => 3,
    'hide_empty_categories' => true,
    'cache_ttl' => 3600,
];
