<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlist) {}

    public function show(): View
    {
        return view('pages.wishlist', ['products' => $this->wishlist->products()]);
    }

    public function summary(): JsonResponse
    {
        return response()->json(['wishlist' => $this->wishlist->summary()]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $added = $this->wishlist->toggle($product);
        $message = $added ? 'Saved to favorites' : 'Removed from favorites';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'added' => $added, 'wishlist' => $this->wishlist->summary()]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, int $productId): JsonResponse|RedirectResponse
    {
        $this->wishlist->remove($productId);

        return $this->respond($request, 'Removed from favorites');
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $this->wishlist->clear();

        return $this->respond($request, 'Favorites cleared');
    }

    public function moveToCart(Request $request, CartService $cart): JsonResponse|RedirectResponse
    {
        $moved = $this->wishlist->moveAllToCart($cart);
        $message = $moved === 1 ? '1 item moved to cart' : "{$moved} items moved to cart";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'cart' => $cart->summary(),
                'wishlist' => $this->wishlist->summary(),
            ]);
        }

        return redirect()->route('cart.show')->with('status', $message);
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'wishlist' => $this->wishlist->summary()]);
        }

        return back()->with('status', $message);
    }
}
