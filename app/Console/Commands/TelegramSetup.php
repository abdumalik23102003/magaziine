<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSetup extends Command
{
    protected $signature = 'telegram:setup {url? : Mini App HTTPS manzili}';

    protected $description = 'Botning menyu tugmasini Mini App ga ulaydi';

    public function handle(): int
    {
        $token = (string) config('telegram.bot_token');
        $url = (string) ($this->argument('url')
            ?: config('telegram.webapp_url')
            ?: rtrim((string) config('app.url'), '/').'/tma');

        if ($token === '') {
            $this->error('TELEGRAM_BOT_TOKEN .env da yo\'q.');

            return self::FAILURE;
        }

        if (! str_starts_with($url, 'https://')) {
            $this->error("Telegram faqat HTTPS manzilni qabul qiladi: {$url}");

            return self::FAILURE;
        }

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/setChatMenuButton", [
                'menu_button' => [
                    'type' => 'web_app',
                    'text' => (string) config('telegram.menu_button_text'),
                    'web_app' => ['url' => $url],
                ],
            ]);
        } catch (ConnectionException) {
            // Xatolik matnida token bor URL bo'ladi: logga va ekranga chiqarilmaydi.
            Log::warning('Telegram setChatMenuButton: connection failed.');
            $this->error('Telegram serveriga ulanib bo\'lmadi.');

            return self::FAILURE;
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            $reason = (string) $response->json('description', 'unknown error');
            Log::warning('Telegram setChatMenuButton failed.', ['status' => $response->status(), 'description' => $reason]);
            $this->error("Telegram rad etdi: {$reason}");

            return self::FAILURE;
        }

        $this->info("Menyu tugmasi ulandi: {$url}");

        return self::SUCCESS;
    }
}
