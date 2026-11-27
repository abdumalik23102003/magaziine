<?php

declare(strict_types=1);

namespace App\Services\TelegramBot;

/** Inline tugmalar yasash uchun kichik yordamchi. callback_data <= 64 bayt! */
final class Kb
{
    /** @return array<string, string> */
    public static function btn(string $text, string $callbackData): array
    {
        return ['text' => $text, 'callback_data' => $callbackData];
    }

    /** @return array<string, string> */
    public static function url(string $text, string $url): array
    {
        return ['text' => $text, 'url' => $url];
    }
}
