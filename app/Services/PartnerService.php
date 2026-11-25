<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Hamkor paneli uchun "faqat o'z brendlari" qoidasi shu yerda bir joyda. */
class PartnerService
{
    /** null = cheklanmagan (admin). Massiv = faqat shu brendlar (bo'sh bo'lishi mumkin). */
    public function brandIds(?User $user): ?array
    {
        if ($user === null) {
            return [];
        }

        if ($user->isAdmin()) {
            return null;
        }

        return $user->brands()->pluck('brands.id')->map(fn ($id): int => (int) $id)->all();
    }

    /** Forma orqali kelgan brand_id hamkorning o'z brendi ekanini server tomonda tekshiradi. */
    public function assertOwnsBrand(User $user, mixed $brandId): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (! $user->ownsBrand(is_numeric($brandId) ? (int) $brandId : null)) {
            throw new AuthorizationException('You can only work with your own brands.');
        }
    }

    public function products(?User $user): Builder
    {
        $ids = $this->brandIds($user);

        return Product::query()->when($ids !== null, fn (Builder $query) => $query->whereIn('brand_id', $ids));
    }

    /** Faqat o'z mahsulotlari kirgan buyurtma qatorlari (o'chirilgan mahsulotlar ham hisobda). */
    public function orderItems(?User $user): Builder
    {
        $ids = $this->brandIds($user);

        return OrderItem::query()->when($ids !== null, fn (Builder $query) => $query->whereHas(
            'variant.product',
            fn (Builder $product) => $product->withTrashed()->whereIn('brand_id', $ids),
        ));
    }

    public function pendingCount(?User $user): int
    {
        return $this->products($user)->where('approval_status', ApprovalStatus::Pending->value)->count();
    }

    /** So'nggi 30 kun sotuvi (bekor qilingan buyurtmalarsiz), faqat o'z mahsulotlari. */
    public function salesLast30Days(?User $user): float
    {
        return (float) $this->orderItems($user)
            ->whereHas('order', fn (Builder $order) => $order
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->where('created_at', '>=', now()->subDays(30)))
            ->sum(DB::raw('order_items.price * order_items.quantity'));
    }

    public function lowStock(?User $user, int $threshold): Builder
    {
        $ids = $this->brandIds($user);

        return ProductVariant::query()
            ->where('is_active', true)
            ->where('stock', '<', $threshold)
            ->whereHas('product', fn (Builder $product) => $product
                ->when($ids !== null, fn (Builder $query) => $query->whereIn('brand_id', $ids)));
    }
}
