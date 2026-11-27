<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TelegramAuthService;
use App\Services\WebSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Saytda "Telegram bilan kirish" tugmasi (Telegram Login Widget).
 * Mini App bilan bir xil telegram_id ga asoslanadi, shuning uchun bitta odam ikkala joyda ham bitta hisobga kiradi.
 */
class TelegramWidgetController extends Controller
{
    public function callback(Request $request, TelegramAuthService $telegram, WebSignIn $signIn): RedirectResponse
    {
        abort_unless(filled(config('telegram.bot_token')) && filled(config('telegram.bot_username')), 404);

        try {
            $user = $telegram->userFor($telegram->verifyWidget($request->query()));
        } catch (ValidationException $e) {
            return redirect()->route('login')->withErrors(['auth' => (string) collect($e->errors())->flatten()->first()]);
        }

        $signIn->login($request, $user);

        return redirect()->intended(route('home'));
    }
}
