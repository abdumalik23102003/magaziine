<?php

return [
    // false bo'lsa, tasdiqlangan mahsulot o'zgarganda ham tasdiqlangan holda qoladi.
    'reapprove_on_change' => (bool) env('PARTNER_REAPPROVE_ON_CHANGE', true),

    // Qaysi o'zgarishlar tasdiqlangan mahsulotni qayta pending ga tushiradi.
    // name, description: asosiy maydonlar. images: rasm qo'shish/o'zgartirish/o'chirish.
    // variants: narx, chegirma, nom, SKU (ombor qoldig'i hech qachon hisobga olinmaydi).
    'watch' => [
        'name' => true,
        'description' => true,
        'images' => true,
        'variants' => false,
    ],

    // Dashboard: qoldig'i shundan kam bo'lgan variantlar "kam qolgan" hisoblanadi.
    'low_stock_threshold' => (int) env('PARTNER_LOW_STOCK', 10),
];
