<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Mehmonni aniqlovchi token. Session id login paytida o'zgaradi, token esa yo'q:
 * shuning uchun mehmon savati/sevimlilarini login'dan keyin foydalanuvchiga ko'chira olamiz.
 */
final class Visitor
{
    private const KEY = 'visitor_token';

    public static function token(): string
    {
        if (! session()->has(self::KEY)) {
            session()->put(self::KEY, Str::random(40));
        }

        return (string) session()->get(self::KEY);
    }

    public static function peek(): ?string
    {
        return session()->get(self::KEY);
    }

    public static function forget(): void
    {
        session()->forget(self::KEY);
    }
}
