<?php

declare(strict_types=1);

namespace App\Services\TelegramBot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class TelegramBotClient
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        $token = (string) config('telegram_bot.token');

        if ($token === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN sozlanmagan.');
        }

        $response = Http::asJson()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/{$method}", $params);

        $json = $response->json();
        $json = is_array($json) ? $json : [];

        if (! $response->successful() || ($json['ok'] ?? false) !== true) {
            // Tokenni logga yozmaymiz.
            Log::warning('Telegram Bot API xatosi', [
                'method' => $method,
                'status' => $response->status(),
                'description' => $json['description'] ?? null,
            ]);
        }

        return $json;
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>|null  $inlineKeyboard
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function sendMessage(int|string $chatId, string $text, ?array $inlineKeyboard = null, array $extra = []): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ];

        if ($inlineKeyboard !== null) {
            $params['reply_markup'] = ['inline_keyboard' => $inlineKeyboard];
        }

        return $this->call('sendMessage', $params + $extra);
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>|null  $inlineKeyboard
     * @return array<string, mixed>
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, ?array $inlineKeyboard = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ];

        if ($inlineKeyboard !== null) {
            $params['reply_markup'] = ['inline_keyboard' => $inlineKeyboard];
        }

        return $this->call('editMessageText', $params);
    }

    /**
     * Callback bosilgan bo'lsa mavjud xabarni tahrirlaydi, aks holda yangi xabar yuboradi.
     *
     * @param  array<int, array<int, array<string, mixed>>>|null  $inlineKeyboard
     * @return array<string, mixed>
     */
    public function reply(BotContext $ctx, string $text, ?array $inlineKeyboard = null): array
    {
        if ($ctx->callbackId !== null && $ctx->messageId !== null) {
            return $this->editMessageText($ctx->chatId, $ctx->messageId, $text, $inlineKeyboard);
        }

        return $this->sendMessage($ctx->chatId, $text, $inlineKeyboard);
    }

    /** @return array<string, mixed> */
    public function answerCallbackQuery(string $callbackId, ?string $text = null, bool $alert = false): array
    {
        return $this->call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $alert ?: null,
        ], static fn ($v) => $v !== null));
    }

    /** @return array<string, mixed> */
    public function setWebhook(string $url, string $secret, bool $dropPending = false): array
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => $dropPending,
        ]);
    }

    /** @return array<string, mixed> */
    public function deleteWebhook(bool $dropPending = false): array
    {
        return $this->call('deleteWebhook', ['drop_pending_updates' => $dropPending]);
    }

    /** @return array<string, mixed> */
    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }

    /**
     * @param  array<int, array{command: string, description: string}>  $commands
     * @return array<string, mixed>
     */
    public function setMyCommands(array $commands): array
    {
        return $this->call('setMyCommands', ['commands' => $commands]);
    }
}
