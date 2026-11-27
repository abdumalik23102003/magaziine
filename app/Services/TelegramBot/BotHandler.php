<?php

declare(strict_types=1);

namespace App\Services\TelegramBot;

/**
 * app/Services/TelegramBot/Handlers/*Handler.php ichidagi har bir sinf shu
 * interfeysni amalga oshirsa, BotRouter uni avtomatik topadi.
 *
 * Handler metodlari imzosi:
 *     public function name(TelegramBotClient $bot, BotContext $ctx, string $arg): void
 */
interface BotHandler
{
    /** @return array<string, string>  buyruq (slashsiz) => metod nomi */
    public function commands(): array;

    /** @return array<string, string>  callback prefiksi ("catalog" in "catalog:2") => metod nomi */
    public function callbacks(): array;

    /** @return array<string, string>  Telegram "/" menyusi va /help uchun: buyruq => tavsif */
    public function menu(): array;
}
