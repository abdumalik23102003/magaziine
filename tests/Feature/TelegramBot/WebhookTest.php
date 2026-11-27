<?php

declare(strict_types=1);

namespace Tests\Feature\TelegramBot;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    private const SECRET = 's3cret_test-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'telegram_bot.token' => '123456:TEST',
            'telegram_bot.webhook_secret' => self::SECRET,
        ]);

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    }

    /** @param  array<string, mixed>  $update */
    private function hook(array $update, ?string $secret = self::SECRET)
    {
        return $this->postJson(
            '/telegram/bot/webhook',
            $update,
            $secret === null ? [] : ['X-Telegram-Bot-Api-Secret-Token' => $secret],
        );
    }

    /** @return array<string, mixed> */
    private function message(int $updateId, string $text, string $chatType = 'private'): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 10,
                'from' => ['id' => 777, 'is_bot' => false, 'first_name' => 'Ali'],
                'chat' => ['id' => 777, 'type' => $chatType],
                'text' => $text,
            ],
        ];
    }

    public function test_rejects_missing_secret(): void
    {
        $this->hook($this->message(1001, '/start'), null)->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_rejects_wrong_secret(): void
    {
        $this->hook($this->message(1002, '/start'), 'wrong')->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_start_sends_greeting_with_menu(): void
    {
        $this->hook($this->message(1003, '/start'))->assertOk();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage')
            && $r['chat_id'] === 777
            && str_contains($r['text'], 'Assalomu alaykum')
            && isset($r['reply_markup']['inline_keyboard']));
    }

    public function test_help_lists_registered_commands(): void
    {
        $this->hook($this->message(1004, '/help'))->assertOk();

        Http::assertSent(fn (Request $r) => str_contains($r['text'], '/settings') && str_contains($r['text'], '/start'));
    }

    public function test_duplicate_update_is_processed_once(): void
    {
        $update = $this->message(1005, '/start');

        $this->hook($update)->assertOk();
        $this->hook($update)->assertOk();

        Http::assertSentCount(1);
    }

    public function test_group_chats_are_ignored(): void
    {
        $this->hook($this->message(1006, '/start', 'group'))->assertOk();
        Http::assertNothingSent();
    }

    public function test_unimplemented_callback_answers_coming_soon(): void
    {
        $this->hook([
            'update_id' => 1007,
            'callback_query' => [
                'id' => 'cb1',
                'from' => ['id' => 777, 'is_bot' => false, 'first_name' => 'Ali'],
                'data' => 'nonexistent:0',
                'message' => ['message_id' => 10, 'chat' => ['id' => 777, 'type' => 'private']],
            ],
        ])->assertOk();

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/answerCallbackQuery')
            && str_contains((string) ($r['text'] ?? ''), 'tez orada'));
    }
}
