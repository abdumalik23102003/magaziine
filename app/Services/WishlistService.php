<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use App\Support\Money;
use App\Support\Visitor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    private function query(): Builder
    {
        $user = Auth::user();

        return WishlistItem::query()->when(
            $user,
            fn (Builder $query) => $query->where('user_id', $user->id),
            fn (Builder $query) => $query->whereNull('user_id')->where('session_id', Visitor::token()),
        );
    }

    private function owner(): array
    {
        $user = Auth::user();

        return $user
            ? ['user_id' => $user->id]
            : ['user_id' => null, 'session_id' => Visitor::token()];
    }

    /** true = qo'shildi, false = olib tashlandi */
    public function toggle(Product $product): bool
    {
        $existing = $this->query()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        WishlistItem::query()->create($this->owner() + ['product_id' => $product->id]);

        return true;
    }

    public function remove(int $productId): void
    {
        $this->query()->where('product_id', $productId)->delete();
    }

    public function clear(): void
    {
        $this->query()->delete();
    }

    /** Faol mahsulotlar, oxirgi saqlangani birinchi. */
    public function products(): Collection
    {
        $ids = $this->query()->orderByDesc('id')->pluck('product_id')->all();

        if ($ids === []) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->forCard()
            ->has('activeVariants')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $products->get($id))->filter()->values();
    }

    public function summary(): array
    {
        $items = [];
        $total = 0;

        foreach ($this->products() as $product) {
            $variant = $product->cardVariant();

            if (! $variant) {
                continue;
            }

            $price = Money::cents($variant->final_price);
            $total += $price;

            $items[] = [
                'id' => $product->id,
                'url' => route('products.show', $product->slug),
                'name' => $product->name,
                'category' => $product->category?->name,
                'img' => $product->mainImage?->url,
                'variant' => $variant->name,
                'price' => Money::format($price),
                'old_price' => $variant->has_discount ? Money::format(Money::cents($variant->price)) : null,
                'discount' => (int) $variant->discount_percentage,
                // omborda yo'q bo'lsa null: "Add" tugmasi o'rniga "Out of stock" chiqadi
                'variant_id' => (float) $variant->stock >= 1 ? $variant->id : null,
            ];
        }

        return [
            'items' => $items,
            'count' => count($items),
            'total' => Money::format($total),
        ];
    }

    /** Hammasini savatga o'tkazadi, o'tganlarini sevimlilardan olib tashlaydi. O'tkazilganlar sonini qaytaradi. */
    public function moveAllToCart(CartService $cart): int
    {
        $moved = 0;

        foreach ($this->summary()['items'] as $item) {
            if (! $item['variant_id']) {
                continue;
            }

            try {
                $cart->add($item['variant_id'], 1);
                $this->remove($item['id']);
                $moved++;
            } catch (ValidationException) {
                // yetarli qoldiq yo'q: sevimlilarda qoladi
            }
        }

        return $moved;
    }

    public function mergeGuestList(User $user, ?string $token): void
    {
        if (! $token) {
            return;
        }

        DB::transaction(function () use ($user, $token): void {
            $guestItems = WishlistItem::query()->whereNull('user_id')->where('session_id', $token)->get();

            foreach ($guestItems as $item) {
                WishlistItem::query()->firstOrCreate(['user_id' => $user->id, 'product_id' => $item->product_id]);
                $item->delete();
            }
        });
    }
}
