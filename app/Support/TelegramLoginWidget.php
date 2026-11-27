<?php

namespace App\Support;

/**
 * Telegram Login Widget (sayt uchun) imzosi.
 * https://core.telegram.org/widgets/login-legacy (Checking authorization)
 *
 * Mini App initData'dan farqi: maxfiy kalit HMAC("WebAppData") emas, to'g'ridan-to'g'ri SHA256(bot token).
 */
final class TelegramLoginWidget
{
    /** @param  array<string, string>  $data  `hash` dan tashqari barcha qabul qilingan maydonlar */
    public static function signature(array $data, string $botToken): string
    {
        unset($data['hash']);
        ksort($data, SORT_STRING);

        $lines = [];

        foreach ($data as $key => $value) {
            $lines[] = $key.'='.$value;
        }

        return hash_hmac('sha256', implode("\n", $lines), hash('sha256', $botToken, true));
    }

    /** @param  array<string, mixed>  $data  so'rov (query) maydonlari, `hash` bilan birga */
    public static function isValid(array $data, string $botToken): bool
    {
        $hash = $data['hash'] ?? null;

        if (! is_string($hash) || $hash === '' || $botToken === '') {
            return false;
        }

        // Telegram faqat oddiy matn maydonlarini yuboradi: massiv yoki boshqa tur kelsa, so'rov soxta.
        foreach ($data as $value) {
            if (! is_string($value)) {
                return false;
            }
        }

        return hash_equals(self::signature($data, $botToken), strtolower($hash));
    }
}
