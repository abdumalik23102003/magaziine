<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    private const PER_PAGE = 12;

    private const SORTS = [
        'latest' => 'Newest',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'name' => 'Name: A-Z',
    ];

    /** Chegirmadan keyingi narx (SQL). Narx bo'yicha saralash shu bilan ishlaydi. */
    private const FINAL_PRICE_SQL = 'product_variants.price * (100 - COALESCE(product_variants.discount_percentage, 0)) / 100';

    public function index(Request $request): View
    {
        return $this->render($request, null);
    }

    public function category(Request $request, Category $category): View
    {
        return $this->render($request, $category);
    }

    private function render(Request $request, ?Category $category): View
    {
        // Query-string dan keladigan hamma qiymat tozalanadi: massiv yoki begona qiymat 500 bermaydi.
        $rawSearch = $request->query('q');
        $search = is_string($rawSearch) ? Str::of($rawSearch)->squish()->limit(100, '')->toString() : '';

        $rawBrand = $request->query('brand');
        $brand = is_string($rawBrand) && $rawBrand !== ''
            ? Brand::query()->where('slug', $rawBrand)->first()
            : null;

        $rawSort = $request->query('sort');
        $sort = is_string($rawSort) && array_key_exists($rawSort, self::SORTS) ? $rawSort : 'latest';

        $discounted = $request->boolean('discounted');

        $query = Product::query()
            ->active()
            ->forCard()
            ->has('activeVariants');

        if ($category) {
            $query->whereIn('category_id', $category->branchIds());
        }

        if ($brand) {
            $query->where('brand_id', $brand->id);
        }

        if ($search !== '') {
            $query->where(function (Builder $where) use ($search) {
                $where->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($discounted) {
            $query->whereHas('activeVariants', fn (Builder $variants) => $variants->where('discount_percentage', '>', 0));
        }

        $this->applySort($query, $sort);

        $products = $query->paginate(self::PER_PAGE)->withQueryString();

        $brands = Brand::query()
            ->whereHas('products', fn (Builder $products) => $products->active()->has('activeVariants'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('pages.catalog', [
            'products' => $products,
            'category' => $category,
            'brands' => $brands,
            'brand' => $brand,
            'search' => $search,
            'sort' => $sort,
            'sorts' => self::SORTS,
            'discounted' => $discounted,
            'breadcrumbs' => $this->breadcrumbs($category),
        ]);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc', 'price_desc' => $query
                ->withMin('activeVariants as min_final_price', DB::raw(self::FINAL_PRICE_SQL))
                ->orderBy('min_final_price', $sort === 'price_asc' ? 'asc' : 'desc')
                ->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };
    }

    private function breadcrumbs(?Category $category): array
    {
        $crumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Catalog', 'url' => $category ? route('catalog.index') : null],
        ];

        if ($category) {
            $category->loadMissing('parent');
            $trail = $category->trail();
            $last = $trail->count() - 1;

            foreach ($trail as $i => $node) {
                $crumbs[] = [
                    'label' => $node->name,
                    'url' => $i === $last ? null : route('catalog.category', $node->slug),
                ];
            }
        }

        return $crumbs;
    }
}
