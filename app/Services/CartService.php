<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use App\Support\Visitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const MAX_QTY = 99;

    /** O'qish uchun: savat bo'lmasa yaratmaydi (har mehmon uchun bo'sh qator yig'ilmasin). */
    private function find(): ?Cart
    {
        $user = Auth::user();

        return $user
            ? Cart::query()->where('user_id', $user->id)->first()
            : Cart::query()->whereNull('user_id')->where('session_id', Visitor::token())->first();
    }

    private function findOrCreate(): Cart
    {
        $user = Auth::user();

        if ($user) {
            return Cart::query()->firstOrCreate(['user_id' => $user->id]);
        }

        return $this->find() ?? Cart::query()->create(['user_id' => null, 'session_id' => Visitor::token()]);
    }

    private function availableVariant(int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->first();

        if (! $variant) {
            throw ValidationException::withMessages(['variant_id' => 'This product is no longer available.']);
        }

        return $variant;
    }

    private function maxFor(ProductVariant $variant): int
    {
        return min(self::MAX_QTY, (int) floor((float) $variant->stock));
    }

    public function add(int $variantId, int $qty = 1): void
    {
        $this->put($variantId, $qty, true);
    }

    public function set(int $variantId, int $qty): void
    {
        if ($qty < 1) {
            $this->remove($variantId);

            return;
        }

        $this->put($variantId, $qty, false);
    }

    /** $increment = true bo'lsa mavjud miqdorga qo'shadi, aks holda aynan shu miqdorni yozadi. */
    private function put(int $variantId, int $qty, bool $increment): void
    {
        $qty = max(1, min(self::MAX_QTY, $qty));
        $variant = $this->availableVariant($variantId);
        $max = $this->maxFor($variant);

        if ($max < 1) {
            throw ValidationException::withMessages(['quantity' => 'Sorry, this item is out of stock.']);
        }

        DB::transaction(function () use ($variant, $qty, $max, $increment): void {
            $cart = $this->findOrCreate();
            $item = $cart->items()->where('product_variant_id', $variant->id)->first();
            $new = $increment ? ($item ? (int) $item->quantity : 0) + $qty : $qty;

            if ($new > $max) {
                throw ValidationException::withMessages(['quantity' => "Only {$max} in stock."]);
            }

            $cart->items()->updateOrCreate(['product_variant_id' => $variant->id], ['quantity' => $new]);
        });
    }

    public function remove(int $variantId): void
    {
        $this->find()?->items()->where('product_variant_id', $variantId)->delete();
    }

    public function clear(): void
    {
        $this->find()?->items()->delete();
    }

    /**
     * Savat holati (JSON-ga tayyor). Yaroqsiz qatorlar (o'chirilgan/nofaol/tugagan) olib tashlanadi,
     * miqdor ombordagi qoldiqdan oshmaydi.
     */
    public function summary(): array
    {
        $cart = $this->find();
        $lines = [];
        $subtotal = 0;
        $count = 0;

        $items = $cart
            ? $cart->items()->with(['variant.product.mainImage', 'variant.product.unit', 'variant.product.category'])->orderBy('id')->get()
            : collect();

        foreach ($items as $item) {
            $variant = $item->variant;
            $product = $variant?->product;
            $max = $variant ? $this->maxFor($variant) : 0;

            if (! $variant || ! $variant->is_active || ! $product || ! $product->is_active || $max < 1) {
                $item->delete();

                continue;
            }

            $qty = min((int) $item->quantity, $max);

            if ($qty !== (int) $item->quantity) {
                $item->update(['quantity' => $qty]);
            }

            $price = Money::cents($variant->final_price);
            $subtotal += $price * $qty;
            $count += $qty;

            $lines[] = [
                'id' => $variant->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'variant' => $variant->name,
                'url' => route('products.show', $product->slug),
                'img' => $product->mainImage?->url,
                'unit' => $product->unit?->short_name,
                'category' => $product->category?->name,
                'price' => Money::format($price),
                'old_price' => $variant->has_discount ? Money::format(Money::cents($variant->price)) : null,
                'discount' => (int) $variant->discount_percentage,
                'qty' => $qty,
                'max' => $max,
                'line_total' => Money::format($price * $qty),
            ];
        }

        $freeFrom = Money::cents(config('shop.free_delivery_from'));
        $taxRate = (float) config('shop.tax_rate');
        $tax = (int) round($subtotal * $taxRate);

        return [
            'items' => $lines,
            'count' => $count,
            'subtotal_cents' => $subtotal,
            'subtotal' => Money::format($subtotal),
            'tax_rate' => $taxRate,
            'tax' => Money::format($tax),
            'total' => Money::format($subtotal + $tax),
            'free_from' => Money::format($freeFrom),
            'remaining' => Money::format(max(0, $freeFrom - $subtotal)),
            'qualifies' => $subtotal > 0 && $subtotal >= $freeFrom,
            'progress' => $freeFrom > 0 ? min(100, (int) round($subtotal / $freeFrom * 100)) : 100,
        ];
    }

    /** Login/ro'yxatdan o'tishdan keyin mehmon savatini foydalanuvchiga qo'shadi. */
    public function mergeGuestCart(User $user, ?string $token): void
    {
        if (! $token) {
            return;
        }

        DB::transaction(function () use ($user, $token): void {
            $guest = Cart::query()->whereNull('user_id')->where('session_id', $token)->first();

            if (! $guest) {
                return;
            }

            $mine = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            foreach ($guest->items as $item) {
                $existing = $mine->items()->where('product_variant_id', $item->product_variant_id)->first();
                $qty = min(self::MAX_QTY, (int) $item->quantity + ($existing ? (int) $existing->quantity : 0));

                $mine->items()->updateOrCreate(['product_variant_id' => $item->product_variant_id], ['quantity' => $qty]);
            }

            $guest->delete();
        });
    }
}
