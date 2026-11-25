<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Model;

/**
 * Product, ProductVariant va ProductImage uchun (AppServiceProvider da ulanadi).
 * Faqat hamkor roli bilan kirgan foydalanuvchi o'zgartirganda ishlaydi: admin, seeder va checkout tegmaydi.
 */
class PartnerChangeObserver
{
    public function __construct(private ProductService $products) {}

    public function updated(Model $model): void
    {
        if (! $model instanceof Product || ! $this->byPartner()) {
            return;
        }

        foreach (['name', 'description'] as $field) {
            if ($model->wasChanged($field) && $this->products->reapproveIfWatched($model, $field)) {
                return;
            }
        }
    }

    public function saved(Model $model): void
    {
        if ($model instanceof ProductVariant) {
            $important = $model->wasRecentlyCreated || $model->wasChanged(['price', 'discount_percentage', 'name', 'sku']);

            if ($important) {
                $this->touch($model->product, 'variants');
            }
        } elseif ($model instanceof ProductImage) {
            $this->touch($model->product, 'images');
        }
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof ProductVariant) {
            $this->touch($model->product, 'variants');
        } elseif ($model instanceof ProductImage) {
            $this->touch($model->product, 'images');
        }
    }

    private function touch(?Product $product, string $area): void
    {
        if ($product && $this->byPartner()) {
            $this->products->reapproveIfWatched($product, $area);
        }
    }

    private function byPartner(): bool
    {
        return (bool) auth()->user()?->hasRole(UserRole::Partner);
    }
}
