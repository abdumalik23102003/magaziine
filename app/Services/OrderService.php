<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private CartService $cart) {}

    /**
     * Savatdan buyurtma yaratadi. Hammasi bitta tranzaksiyada: narxlar serverdan olinadi,
     * ombor qoldig'i shartli UPDATE bilan kamayadi (bir vaqtda ikki kishi oxirgi donani ololmaydi).
     *
     * @param  array{name:string, phone:string, address:string, delivery_method:string, payment_method:string, note?:?string, delivery?:array<string, mixed>}  $data
     */
    public function place(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data): Order {
            $summary = $this->cart->summary();

            if ($summary['items'] === []) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $subtotal = $summary['subtotal_cents'];
            $tax = (int) round($subtotal * (float) config('shop.tax_rate'));
            $freeFrom = Money::cents(config('shop.free_delivery_from'));
            $fee = $subtotal >= $freeFrom
                ? 0
                : Money::cents(config('shop.delivery_methods')[$data['delivery_method']]['fee']);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'customer_name' => $data['name'],
                'status' => OrderStatus::Pending,
                'subtotal' => Money::format($subtotal),
                'tax' => Money::format($tax),
                'delivery_fee' => Money::format($fee),
                'total_price' => Money::format($subtotal + $tax + $fee),
                'delivery_method' => $data['delivery_method'],
                'payment_method' => $data['payment_method'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'note' => $data['note'] ?? null,
            ] + Arr::only($data['delivery'] ?? [], Order::DELIVERY_FIELDS));

            foreach ($summary['items'] as $line) {
                $updated = ProductVariant::query()
                    ->whereKey($line['id'])
                    ->where('stock', '>=', $line['qty'])
                    ->decrement('stock', $line['qty']);

                if ($updated === 0) {
                    throw ValidationException::withMessages([
                        'cart' => "Sorry, \"{$line['name']}\" just ran out of stock. Please review your cart.",
                    ]);
                }

                $order->items()->create([
                    'product_variant_id' => $line['id'],
                    'product_name' => $line['name'],
                    'variant_name' => $line['variant'],
                    'price' => $line['price'],
                    'quantity' => $line['qty'],
                ]);
            }

            $this->cart->clear();

            return $order;
        });
    }
}
