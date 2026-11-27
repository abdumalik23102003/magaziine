<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TelegramBot\BotRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class TelegramBotWebhookController
{
    public function __invoke(Request $request, BotRouter $router): JsonResponse
    {
        $secret = (string) config('telegram_bot.webhook_secret');
        $given = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === '' || ! hash_equals($secret, $given)) {
            abort(403);
        }

        $update = $request->json()->all();
        $updateId = $update['update_id'] ?? null;

        if (! is_int($updateId)) {
            return response()->json(['ok' => true]);
        }

        // Telegram qayta yuborsa (retry/replay), bir update ikki marta ishlanmaydi.
        if (! Cache::add("tg:bot:update:{$updateId}", 1, now()->addHour())) {
            return response()->json(['ok' => true]);
        }

        try {
            $router->dispatch($update);
        } catch (Throwable $e) {
            report($e);
        }

        // Har doim 200: aks holda Telegram bir xil update'ni qayta-qayta yuboradi.
        return response()->json(['ok' => true]);
    }
}
