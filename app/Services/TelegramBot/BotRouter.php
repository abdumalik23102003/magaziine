<?php

declare(strict_types=1);

namespace App\Services\TelegramBot;

final class BotRouter
{
    /** @var list<BotHandler>|null */
    private ?array $handlers = null;

    public function __construct(private readonly TelegramBotClient $bot)
    {
    }

    /** @return list<BotHandler> */
    public function handlers(): array
    {
        if ($this->handlers !== null) {
            return $this->handlers;
        }

        $found = [];

        foreach (glob(app_path('Services/TelegramBot/Handlers/*Handler.php')) ?: [] as $file) {
            $class = 'App\\Services\\TelegramBot\\Handlers\\'.basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $handler = app($class);

            if ($handler instanceof BotHandler) {
                $found[] = $handler;
            }
        }

        return $this->handlers = $found;
    }

    /** @param  array<string, mixed>  $update */
    public function dispatch(array $update): void
    {
        $ctx = BotContext::fromUpdate($update);

        if ($ctx === null) {
            return;
        }

        if ($ctx->callbackId !== null) {
            $this->dispatchCallback($ctx);

            return;
        }

        $this->dispatchMessage($ctx);
    }

    private function dispatchCallback(BotContext $ctx): void
    {
        [$prefix, $arg] = array_pad(explode(':', $ctx->data, 2), 2, '');

        foreach ($this->handlers() as $handler) {
            $method = $handler->callbacks()[$prefix] ?? null;

            if ($method !== null) {
                $this->bot->answerCallbackQuery((string) $ctx->callbackId);
                $handler->{$method}($this->bot, $ctx, $arg);

                return;
            }
        }

        $this->bot->answerCallbackQuery((string) $ctx->callbackId, "Bu bo'lim tez orada ishga tushadi.");
    }

    private function dispatchMessage(BotContext $ctx): void
    {
        if ($ctx->text === '') {
            return;
        }

        if (preg_match('~^/([A-Za-z0-9_]+)(?:@[A-Za-z0-9_]+)?(?:\s+(.*))?$~su', $ctx->text, $m) === 1) {
            $command = strtolower($m[1]);
            $arg = trim($m[2] ?? '');

            foreach ($this->handlers() as $handler) {
                $method = $handler->commands()[$command] ?? null;

                if ($method !== null) {
                    $handler->{$method}($this->bot, $ctx, $arg);

                    return;
                }
            }

            $this->bot->sendMessage($ctx->chatId, "Noma'lum buyruq. /help ni bosing.");

            return;
        }

        foreach ($this->handlers() as $handler) {
            if ($handler instanceof HandlesText && $handler->handleText($this->bot, $ctx)) {
                return;
            }
        }

        $this->bot->sendMessage($ctx->chatId, 'Tushunmadim 🤔 /menu yoki /help ni bosing.');
    }
}
