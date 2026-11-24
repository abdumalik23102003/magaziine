<?php

return [

    /* Header tugmasida joylashuv aniqlanmaguncha ko'rinadigan nom. */
    'default' => env('LOCATION_DEFAULT', 'Downtown Chicago'),

    /* OpenStreetMap Nominatim (bepul). Siyosat: o'zingizni tanishtiruvchi User-Agent shart, ~1 so'rov/soniya. */
    'nominatim_url' => env('LOCATION_NOMINATIM_URL', 'https://nominatim.openstreetmap.org/reverse'),
    'user_agent' => env('LOCATION_USER_AGENT', 'FoodMart/1.0 (admin@example.com)'),

    /* Bir xil hudud (koordinata ~1 km ga yaxlitlanadi) uchun javob keshi, soniyada. */
    'cache_ttl' => 60 * 60 * 24,

    /* Tugmadagi nomning maksimal uzunligi. */
    'label_max' => 24,

    /* Shtat/viloyat qisqartmasi (US-IL kabi) ko'rsatiladigan davlatlar; boshqalarda davlat kodi (UZ). */
    'region_code_countries' => ['us', 'ca', 'au'],

];
