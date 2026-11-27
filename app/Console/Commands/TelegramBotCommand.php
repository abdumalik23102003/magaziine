<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramBot\BotRouter;
use App\Services\TelegramBot\TelegramBotClient;
use Illuminate\Console\Command;

final class TelegramBotCommand extends Command
{
    protected $signature = 'telegram:bot {action : set|info|delete|commands} {--drop-pending : kutayotgan updatelarni tashlab yuborish}';

    protected $description = 'Telegram bot webhook va buyruqlar menyusini boshqarish';

    public function handle(TelegramBotClient $bot, BotRouter $router): int
    {
        return match ($this->argument('action')) {
            'set' => $this->set($bot),
            'info' => $this->show($bot->getWebhookInfo()),
            'delete' => $this->show($bot->deleteWebhook((bool) $this->option('drop-pending'))),
            'commands' => $this->commands($bot, $router),
            default => $this->fail('Noma\'lum action. set|info|delete|commands'),
        };
    }

    private function set(TelegramBotClient $bot): int
    {
        $secret = (string) config('telegram_bot.webhook_secret');

        if (preg_match('/^[A-Za-z0-9_-]{1,256}$/', $secret) !== 1) {
            $this->error('TELEGRAM_BOT_WEBHOOK_SECRET noto\'g\'ri/bo\'sh (A-Z a-z 0-9 _ - ; 1..256). Masalan: openssl rand -hex 32');

            return self::FAILURE;
        }

        $url = (string) (config('telegram_bot.webhook_url') ?: route('telegram.bot.webhook'));

        if (! str_starts_with($url, 'https://')) {
            $this->error("Telegram faqat HTTPS qabul qiladi. Hozirgi URL: {$url}");
            $this->line('Lokal uchun tunnel (sail share / ngrok / cloudflared) ishlating va TELEGRAM_BOT_WEBHOOK_URL ni sozlang.');

            return self::FAILURE;
        }

        $this->info("Webhook: {$url}");

        return $this->show($bot->setWebhook($url, $secret, (bool) $this->option('drop-pending')));
    }

    private function commands(TelegramBotClient $bot, BotRouter $router): int
    {
        $list = [];

        foreach ($router->handlers() as $handler) {
            foreach ($handler->menu() as $command => $description) {
                $list[] = ['command' => strtolower($command), 'description' => mb_substr($description, 0, 256)];
            }
        }

        $this->table(['command', 'description'], $list);

        return $this->show($bot->setMyCommands($list));
    }

    /** @param  array<string, mixed>  $result */
    private function show(array $result): int
    {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ($result['ok'] ?? false) === true ? self::SUCCESS : self::FAILURE;
    }
}
