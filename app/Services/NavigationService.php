<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/** Header dropdownlari: "Sahifalar" (config) va "Katalog" (keshlanadi, ~4 ta so'rov). */
class NavigationService
{
    public const CACHE_KEY = 'navigation.catalog.v1';

    public function pages(bool $authenticated): array
    {
        return collect(config('navigation.pages', []))
            ->filter(fn (array $item): bool => ! array_key_exists('auth', $item) || $item['auth'] === $authenticated)
            ->map(function (array $item): array {
                $item['url'] = isset($item['route']) && Route::has($item['route'])
                    ? route($item['route']).(isset($item['fragment']) ? '#'.$item['fragment'] : '')
                    : null;

                return $item;
            })
            ->filter(fn (array $item): bool => $item['url'] !== null || isset($item['event']))
            ->values()
            ->all();
    }

    /** Faqat slug va nomlar keshlanadi; URL view ichida route() bilan quriladi. */
    public function catalog(): array
    {
        return Cache::remember(self::CACHE_KEY, (int) config('navigation.cache_ttl', 3600), fn (): array => $this->build());
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function build(): array
    {
        $sellable = Product::query()->active()->has('activeVariants')->distinct()->pluck('category_id')->all();

        $categories = Category::query()
            ->when(Schema::hasColumn('categories', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug']);

        $parents = $categories->pluck('parent_id', 'id');
        $has = [];

        foreach ($sellable as $id) {
            for ($guard = 0; $id && $guard < 10; $guard++) {
                $has[$id] = true;
                $id = $parents[$id] ?? null;
            }
        }

        $hideEmpty = (bool) config('navigation.hide_empty_categories', true);
        $visible = fn ($category): bool => ! $hideEmpty || isset($has[$category->id]);
        $byParent = $categories->groupBy('parent_id');

        $tree = $byParent->get(null, collect())
            ->filter($visible)
            ->map(fn ($root): array => [
                'name' => $root->name,
                'slug' => $root->slug,
                'children' => $byParent->get($root->id, collect())->filter($visible)
                    ->map(fn ($child): array => ['name' => $child->name, 'slug' => $child->slug])->values()->all(),
            ])
            ->values()
            ->all();

        return [
            'categories' => $tree,
            'brands' => Brand::query()->active()->featured()->ordered()
                ->limit((int) config('navigation.featured_brands_limit', 8))
                ->get(['id', 'name', 'slug'])
                ->map(fn ($brand): array => ['name' => $brand->name, 'slug' => $brand->slug])->all(),
            'posts' => Post::query()->published()->newest()
                ->limit((int) config('navigation.latest_posts', 3))
                ->get(['id', 'title', 'slug'])
                ->map(fn ($post): array => ['title' => $post->title, 'slug' => $post->slug])->all(),
        ];
    }
}
