<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(): View
    {
        return view('pages.cart', ['cart' => $this->cart->summary()]);
    }

    public function summary(): JsonResponse
    {
        return response()->json(['cart' => $this->cart->summary()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $this->cart->add((int) $data['variant_id'], (int) ($data['quantity'] ?? 1));

        return $this->respond($request, 'Added to cart');
    }

    public function update(Request $request, int $variantId): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        $this->cart->set($variantId, (int) $data['quantity']);

        return $this->respond($request, 'Cart updated');
    }

    public function destroy(Request $request, int $variantId): JsonResponse|RedirectResponse
    {
        $this->cart->remove($variantId);

        return $this->respond($request, 'Item removed');
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart->clear();

        return $this->respond($request, 'Cart cleared');
    }

    /** JS (fetch) uchun JSON, oddiy forma uchun redirect: sahifa JS'siz ham ishlaydi. */
    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cart' => $this->cart->summary()]);
        }

        return back()->with('status', $message);
    }
}
