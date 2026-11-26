<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\SupportChatService;
use App\Services\TelegramAuthService;
use App\Services\WishlistService;
use App\Support\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function store(
        Request $request,
        TelegramAuthService $telegram,
        CartService $cart,
        WishlistService $wishlist,
        SupportChatService $support,
    ): JsonResponse {
        $data = $request->validate(['init_data' => ['required', 'string', 'max:8192']]);

        $user = $telegram->userFor($telegram->verify($data['init_data']));
        $intended = $request->session()->pull('tma.intended');

        if (! $request->user()?->is($user)) {
            $guestToken = Visitor::peek();

            // Boshqa hisob ochiq qolgan bo'lsa, uning sessiyasi yangi foydalanuvchiga o'tmasin.
            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $guestToken = null;
                $intended = null;
            }

            Auth::login($user);
            $request->session()->regenerate();

            $support->adoptGuest($user);
            $cart->mergeGuestCart($user, $guestToken);
            $wishlist->mergeGuestList($user, $guestToken);
            Visitor::forget();
        }

        return response()->json(['redirect' => $intended ?? route('tma.home')]);
    }
}
