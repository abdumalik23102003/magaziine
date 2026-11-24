<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use App\Support\ProductSorting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class BrandService
{
    public const CAROUSEL_KEY = 'brands.carousel';

    public const CAROUSEL_LIMIT = 12;

    public const PER_PAGE = 12;

    private const CAROUSEL_TTL = 300;

    /** Bosh sahifa karuseli: faol va featured brendlar (kesh, brend o'zgarganda BrandObserver tozalaydi). */
    public function carousel(): array
    {
        return Cache::remember(self::CAROUSEL_KEY, self::CAROUSEL_TTL, fn (): array => Brand::query()
            ->active()
            ->featured()
            ->withProductsCount()
            ->ordered()
            ->limit(self::CAROUSEL_LIMIT)
            ->get()
            ->map(fn (Brand $brand): array => $this->card($brand))
            ->all());
    }

    public function flush(): void
    {
        Cache::forget(self::CAROUSEL_KEY);
    }

    /** /brands sahifasi: faol brendlar, sahifalangan. */
    public function listing(): LengthAwarePaginator
    {
        return Brand::query()
            ->active()
            ->withProductsCount()
            ->ordered()
            ->paginate(self::PER_PAGE)
            ->through(fn (Brand $brand): array => $this->card($brand));
    }

    /** Brendning faol va sotuvdagi mahsulotlari (katalog bilan bir xil saralash va sahifalash). */
    public function products(Brand $brand, string $sort): LengthAwarePaginator
    {
        $query = $this->sellable($brand)->forCard();

        ProductSorting::apply($query, $sort);

        return $query->paginate(ProductSorting::PER_PAGE)->withQueryString();
    }

    /** @return array{products:int, categories:int, deals:int} */
    public function stats(Brand $brand): array
    {
        $row = $this->sellable($brand)
            ->selectRaw('COUNT(*) AS total, COUNT(DISTINCT category_id) AS categories')
            ->first();

        $deals = $this->sellable($brand)
            ->whereHas('activeVariants', fn (Builder $variants) => $variants->where('discount_percentage', '>', 0))
            ->count();

        return [
            'products' => (int) $row->total,
            'categories' => (int) $row->categories,
            'deals' => $deals,
        ];
    }

    /** schema.org Organization (JSON-LD). */
    public function schema(Brand $brand): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $brand->name,
            'url' => route('brands.show', $brand),
            'logo' => $brand->logo_url,
            'description' => $brand->tagline ?: null,
            'sameAs' => $brand->website_url ? [$brand->website_url] : null,
        ]);
    }

    /** Karta uchun massiv (products_count: withProductsCount() bilan yuklangan bo'lishi kerak). */
    public function card(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'tagline' => $brand->tagline,
            'logo_url' => $brand->logo_url,
            'cover_url' => $brand->cover_url,
            'products_count' => (int) ($brand->products_count ?? 0),
            'url' => route('brands.show', $brand),
        ];
    }

    private function sellable(Brand $brand): Builder
    {
        return Product::query()
            ->active()
            ->has('activeVariants')
            ->where('brand_id', $brand->id);
    }
}
