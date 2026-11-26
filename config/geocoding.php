<?php

return [

    /*
     | Teskari geokodlash va manzil qidirish provayderi. Hozircha: nominatim.
     | Boshqa provayder qo'shish: App\Services\Geocoding\Geocoder ni amalga oshiring va
     | AppServiceProvider::register() dagi match ga qo'shing; checkout kodiga tegish shart emas.
     */
    'driver' => env('GEOCODING_DRIVER', 'nominatim'),

    'nominatim' => [
        /* Ommaviy nominatim.openstreetmap.org: ~1 so'rov/soniya, o'zingizni tanishtiruvchi User-Agent SHART,
           avtomatik to'ldirish (har harfda so'rov) taqiqlangan. Katta trafik uchun o'z Nominatim serveringiz yoki
           pullik provayder (LocationIQ, Geoapify va h.k. Nominatim formatida) ishlating va URL ni almashtiring. */
        'reverse_url' => env('GEOCODING_REVERSE_URL', env('LOCATION_NOMINATIM_URL', 'https://nominatim.openstreetmap.org/reverse')),
        'search_url' => env('GEOCODING_SEARCH_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env('LOCATION_USER_AGENT', 'FoodMart/1.0 (admin@example.com)'),
        'email' => env('GEOCODING_EMAIL'),
        'api_key' => env('GEOCODING_API_KEY'),
        'api_key_param' => env('GEOCODING_API_KEY_PARAM', 'key'),
        'language' => env('GEOCODING_LANGUAGE', 'en'),
        /* Qidiruvni davlatlar bilan cheklash, masalan "uz" yoki "uz,kz". Bo'sh = cheklovsiz. */
        'countrycodes' => env('GEOCODING_COUNTRYCODES'),
        'timeout' => 5,
        /* Provayderga bir soniyada ketadigan eng ko'p so'rov (0 = cheklanmagan, faqat o'z serveringiz uchun). */
        'rate_per_second' => (int) env('GEOCODING_RATE_PER_SECOND', 1),
    ],

    /* Natijalar keshi (soniya). Siyosat keshlashni ruxsat etadi va talab qiladi. */
    'cache_ttl' => 60 * 60 * 24 * 30,
    'cache_miss_ttl' => 60 * 10,

    /* true bo'lsa, buyurtma uchun xaritada pin qo'yish majburiy. Odatda false: qo'lda yozish ham mumkin. */
    'require_pin' => (bool) env('CHECKOUT_REQUIRE_PIN', false),

    'map' => [
        /* Pin yo'q va oldingi buyurtma bo'lmaganda xarita markazi (standart: Toshkent). */
        'center_lat' => (float) env('MAP_CENTER_LAT', 41.2995),
        'center_lng' => (float) env('MAP_CENTER_LNG', 69.2401),
        'zoom' => (int) env('MAP_ZOOM', 12),
        /* Ommaviy OSM tile serveri foydalanish siyosatiga bo'ysunadi; katta trafikda tile provayder ishlating. */
        'tiles' => env('MAP_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('MAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'),
    ],
];
