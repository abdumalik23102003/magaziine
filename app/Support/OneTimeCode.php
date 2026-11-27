<?php

namespace App\Support;

/** Emailga yuboriladigan bir martalik raqamli kod. */
final class OneTimeCode
{
    public const LENGTH = 6;

    public static function generate(): string
    {
        return str_pad((string) random_int(0, (10 ** self::LENGTH) - 1), self::LENGTH, '0', STR_PAD_LEFT);
    }

    /** Kod bazada ochiq saqlanmaydi: email va kod berilgan kalit (APP_KEY) bilan HMAC qilinadi. */
    public static function hash(string $email, string $code, string $key): string
    {
        return hash_hmac('sha256', $email.'|'.$code, $key);
    }
}
