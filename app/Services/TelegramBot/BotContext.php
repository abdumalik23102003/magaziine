<?php

declare(strict_types=1);

namespace App\Services\TelegramBot;

/**
 * Bitta update'dan olingan, handlerlar uchun qulay ko'rinish.
 * Faqat private chatlar qabul qilinadi.
 */
final readonly class BotContext
{
    /**
     * @param  array<string, mixed>  $from
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public int $chatId,
        public int $telegramId,
        public ?int $messageId,
        public ?string $callbackId,
        public string $text,
        public string $data,
        public array $from,
        public array $raw,
    ) {
    }

    /** @param  array<string, mixed>  $update */
    public static function fromUpdate(array $update): ?self
    {
        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $cb = $update['callback_query'];
            $msg = is_array($cb['message'] ?? null) ? $cb['message'] : [];
            $chat = is_array($msg['chat'] ?? null) ? $msg['chat'] : null;
            $from = is_array($cb['from'] ?? null) ? $cb['from'] : null;

            if ($chat === null || $from === null || ($chat['type'] ?? '') !== 'private'
                || ! isset($chat['id'], $from['id'], $cb['id'])) {
                return null;
            }

            return new self(
                (int) $chat['id'],
                (int) $from['id'],
                isset($msg['message_id']) ? (int) $msg['message_id'] : null,
                (string) $cb['id'],
                '',
                (string) ($cb['data'] ?? ''),
                $from,
                $update,
            );
        }

        if (isset($update['message']) && is_array($update['message'])) {
            $msg = $update['message'];
            $chat = is_array($msg['chat'] ?? null) ? $msg['chat'] : null;
            $from = is_array($msg['from'] ?? null) ? $msg['from'] : null;

            if ($chat === null || $from === null || ($chat['type'] ?? '') !== 'private'
                || ! isset($chat['id'], $from['id']) || ($from['is_bot'] ?? false) === true) {
                return null;
            }

            return new self(
                (int) $chat['id'],
                (int) $from['id'],
                isset($msg['message_id']) ? (int) $msg['message_id'] : null,
                null,
                trim((string) ($msg['text'] ?? '')),
                '',
                $from,
                $update,
            );
        }

        return null;
    }

    public function firstName(): string
    {
        return trim((string) ($this->from['first_name'] ?? ''));
    }
}
