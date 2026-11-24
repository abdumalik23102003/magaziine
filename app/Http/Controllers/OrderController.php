<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $this->authorizeOwner($request, $order);

        return view('pages.order', ['order' => $order->load('items')]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        if (! $order->isCancellableByCustomer()) {
            return back()->with('status', 'This order can no longer be cancelled.');
        }

        $order->cancel();

        return back()->with('status', "Order {$order->number} cancelled.");
    }

    public function reorder(Request $request, Order $order, CartService $cart): RedirectResponse
    {
        $this->authorizeOwner($request, $order);

        $added = 0;

        foreach ($order->items()->get() as $item) {
            if (! $item->product_variant_id) {
                continue;
            }

            try {
                $cart->add($item->product_variant_id, max(1, (int) $item->quantity));
                $added++;
            } catch (ValidationException) {
                // mahsulot tugagan yoki o'chirilgan: o'tkazib yuboramiz
            }
        }

        $message = $added > 0 ? "{$added} item(s) added to your cart." : 'None of these items are available right now.';

        return redirect()->route('cart.show')->with('status', $message);
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }
}
