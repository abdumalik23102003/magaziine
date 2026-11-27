<?php

declare(strict_types=1);

namespace App\Services\TelegramBot;

/** Buyruq bo'lmagan oddiy matnni qayta ishlaydigan handlerlar uchun (qidiruv, izoh...). */
interface HandlesText
{
    /** true qaytarsa, matn qayta ishlangan hisoblanadi. */
    public function handleText(TelegramBotClient $bot, BotContext $ctx): bool;
}
