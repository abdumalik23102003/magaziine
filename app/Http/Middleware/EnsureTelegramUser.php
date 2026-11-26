<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Mini App sahifalari faqat Telegram orqali kirgan foydalanuvchiga ochiladi. */
class EnsureTelegramUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->telegram_id === null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Open the shop from Telegram.'], 401);
            }

            if ($request->isMethod('GET')) {
                $request->session()->put('tma.intended', $request->fullUrl());
            }

            return redirect()->route('tma.login');
        }

        return $next($request);
    }
}
