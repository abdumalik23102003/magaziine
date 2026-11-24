<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductSorting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
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

        // ?brand=slug: faqat faol brend. Nofaol/noma'lum slug e'tiborsiz qoldiriladi.
        $rawBrand = $request->query('brand');
        $brand = is_string($rawBrand) && $rawBrand !== ''
            ? Brand::query()->active()->where('slug', $rawBrand)->first()
            : null;

        $sort = ProductSorting::resolve($request->query('sort'));

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

        ProductSorting::apply($query, $sort);

        $products = $query->paginate(ProductSorting::PER_PAGE)->withQueryString();

        $brands = Brand::query()
            ->active()
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
            'sorts' => ProductSorting::OPTIONS,
            'discounted' => $discounted,
            'breadcrumbs' => $this->breadcrumbs($category),
        ]);
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
