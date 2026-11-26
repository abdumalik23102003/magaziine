<?php

namespace App\Services\Geocoding;

/** Checkout faqat shu interfeysga tayanadi: provayderni almashtirish uchun yangi implementatsiya yetarli. */
interface Geocoder
{
    /** Koordinata bo'yicha manzil. Hech narsa topilmasa null. */
    public function reverse(float $lat, float $lng): ?GeocodedAddress;

    /**
     * Foydalanuvchi aniq so'ragan matn bo'yicha qidiruv (avtomatik to'ldirish EMAS).
     *
     * @return array<int, GeocodedAddress>
     */
    public function search(string $query): array;
}
