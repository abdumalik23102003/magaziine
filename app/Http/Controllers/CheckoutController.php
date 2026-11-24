<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cart->summary();

        if ($cart['items'] === []) {
            return redirect()->route('catalog.index')->with('status', 'Your cart is empty. Add something first.');
        }

        $freeFrom = Money::cents(config('shop.free_delivery_from'));
        $methods = [];

        foreach (config('shop.delivery_methods') as $key => $method) {
            $fee = $cart['subtotal_cents'] >= $freeFrom ? 0 : Money::cents($method['fee']);
            $methods[$key] = $method + ['fee_value' => Money::format($fee)];
        }

        return view('pages.checkout', [
            'cart' => $cart,
            'methods' => $methods,
            'payments' => config('shop.payment_methods'),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:7', 'max:32', 'regex:/^[0-9+()\-\s]+$/'],
            'city' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:150'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'delivery_method' => ['required', Rule::in(array_keys(config('shop.delivery_methods')))],
            'payment_method' => ['required', Rule::in(array_keys(config('shop.payment_methods')))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $address = collect([$data['address'], $data['apartment'] ?? null, $data['city']])
            ->filter()
            ->implode(', ');

        $order = $this->orders->place($request->user(), [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => Str::limit($address, 255, ''),
            'delivery_method' => $data['delivery_method'],
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('orders.show', $order)
            ->with('status', "Order {$order->number} placed. Thank you!");
    }
}
