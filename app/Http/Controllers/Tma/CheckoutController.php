<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\CheckoutController as WebCheckoutController;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cartService): View|RedirectResponse
    {
        $cart = $cartService->summary();

        if ($cart['items'] === []) {
            return redirect()->route('tma.home')->with('status', 'Your cart is empty. Add something first.');
        }

        $freeFrom = Money::cents(config('shop.free_delivery_from'));
        $methods = [];

        foreach (config('shop.delivery_methods') as $key => $method) {
            $fee = $cart['subtotal_cents'] >= $freeFrom ? 0 : Money::cents($method['fee']);
            $methods[$key] = $method + ['fee_value' => Money::format($fee)];
        }

        $user = $request->user();

        return view('tma.checkout', [
            'cart' => $cart,
            'methods' => $methods,
            'payments' => config('shop.payment_methods'),
            'user' => $user,
            'phone' => $user->phone ?: $user->orders()->latest('id')->value('phone'),
        ]);
    }

    /** Tekshiruv va buyurtma yaratish saytdagi bilan bitta joyda: faqat yakuniy sahifa boshqacha. */
    public function store(Request $request, WebCheckoutController $checkout): RedirectResponse
    {
        $checkout->store($request);

        $order = $request->user()->orders()->latest('id')->firstOrFail();

        return redirect()
            ->route('tma.orders.show', $order)
            ->with('status', "Order {$order->number} placed. Thank you!");
    }
}
