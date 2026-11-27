<?php

declare(strict_types=1);

namespace App\Services\TelegramBot\Handlers;

use App\Models\User;
use App\Services\TelegramBot\BotContext;
use App\Services\TelegramBot\BotHandler;
use App\Services\TelegramBot\Kb;
use App\Services\TelegramBot\TelegramBotClient;
use Throwable;

final class SettingsHandler implements BotHandler
{
    public function commands(): array
    {
        return ['settings' => 'show'];
    }

    public function callbacks(): array
    {
        return ['settings' => 'show'];
    }

    public function menu(): array
    {
        return ['settings' => 'Sozlamalar'];
    }

    public function show(TelegramBotClient $bot, BotContext $ctx, string $arg): void
    {
        $linked = $this->isLinked($ctx->telegramId);

        $text = "<b>⚙️ Sozlamalar</b>\n\n"
            ."Telegram ID: <code>{$ctx->telegramId}</code>\n"
            .'Saytdagi hisob: '.($linked ? 'ulangan ✅' : 'hali ulanmagan')."\n"
            ."Til: O'zbekcha";

        $bot->reply($ctx, $text, [[Kb::btn('⬅️ Menyu', 'menu')]]);
    }

    private function isLinked(int $telegramId): bool
    {
        try {
            return User::query()->where('telegram_id', $telegramId)->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
