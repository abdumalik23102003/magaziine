<?php

namespace App\Support;

/** Pulni butun sent (int) ko'rinishida hisoblaymiz: float xatolari bo'lmasin. */
final class Money
{
    public static function cents(string|int|float|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
