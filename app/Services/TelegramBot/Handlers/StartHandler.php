<?php

declare(strict_types=1);

namespace App\Services\TelegramBot\Handlers;

use App\Services\TelegramBot\BotContext;
use App\Services\TelegramBot\BotHandler;
use App\Services\TelegramBot\BotRouter;
use App\Services\TelegramBot\Kb;
use App\Services\TelegramBot\TelegramBotClient;

final class StartHandler implements BotHandler
{
    public function commands(): array
    {
        return ['start' => 'start', 'menu' => 'showMenu', 'help' => 'help'];
    }

    public function callbacks(): array
    {
        return ['menu' => 'showMenu', 'help' => 'help'];
    }

    public function menu(): array
    {
        return [
            'start' => 'Botni boshlash',
            'menu' => 'Asosiy menyu',
            'help' => 'Yordam',
        ];
    }

    public function start(TelegramBotClient $bot, BotContext $ctx, string $arg): void
    {
        $name = e($ctx->firstName() !== '' ? $ctx->firstName() : "do'stim");
        $shop = e((string) config('app.name'));

        $text = "Assalomu alaykum, <b>{$name}</b>! 👋\n\n"
            ."<b>{$shop}</b> do'koniga xush kelibsiz.\n"
            ."Bu yerda mahsulotlarni ko'rish, qidirish va buyurtma berish mumkin.\n\n"
            .'Quyidagi bo\'limlardan birini tanlang:';

        $bot->sendMessage($ctx->chatId, $text, $this->mainKeyboard());
    }

    public function help(TelegramBotClient $bot, BotContext $ctx, string $arg): void
    {
        $lines = ["<b>Yordam</b>\n"];

        foreach (app(BotRouter::class)->handlers() as $handler) {
            foreach ($handler->menu() as $command => $description) {
                $lines[] = '/'.$command.' — '.e($description);
            }
        }

        $lines[] = "\nSavollar bo'lsa, shu yerga yozing yoki saytimizdagi aloqa bo'limidan foydalaning.";

        $bot->reply($ctx, implode("\n", $lines), [[Kb::btn('⬅️ Menyu', 'menu')]]);
    }

    /** /menu buyrug'i va "menu" callback'i uchun */
    public function showMenu(TelegramBotClient $bot, BotContext $ctx, string $arg): void
    {
        $bot->reply($ctx, '<b>Asosiy menyu</b>', $this->mainKeyboard());
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    private function mainKeyboard(): array
    {
        return [
            [Kb::btn('🛍 Mahsulotlar', 'catalog:0'), Kb::btn('🛒 Savat', 'cart:show')],
            [Kb::btn('🔎 Qidirish', 'search:ask'), Kb::btn('⚙️ Sozlamalar', 'settings:home')],
            [Kb::btn('❓ Yordam', 'help')],
        ];
    }
}
