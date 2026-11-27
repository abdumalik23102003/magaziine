<?php

namespace App\Services;

use App\Models\User;
use App\Support\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sayt (web) sessiyasini ochadi va mehmon savati, sevimlilari hamda support chatini hisobga ko'chiradi.
 * AuthController::login bilan bir xil tartib: mehmon tokeni sessiya yangilanishidan OLDIN olinadi.
 */
class WebSignIn
{
    public function __construct(
        private CartService $cart,
        private WishlistService $wishlist,
        private SupportChatService $support,
    ) {}

    public function login(Request $request, User $user, bool $remember = false): void
    {
        $token = Visitor::peek();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->support->adoptGuest($user);
        $this->cart->mergeGuestCart($user, $token);
        $this->wishlist->mergeGuestList($user, $token);
        Visitor::forget();
    }
}
