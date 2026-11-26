<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    private const PER_PAGE = 12;

    public function catalog(Request $request): View
    {
        $rawSearch = $request->query('q');
        $search = is_string($rawSearch) ? Str::of($rawSearch)->squish()->limit(100, '')->toString() : '';
        $discounted = $request->boolean('discounted');

        $categories = Category::query()->roots()->orderBy('name')->get(['id', 'parent_id', 'name', 'slug']);
        $category = $categories->firstWhere('slug', $request->query('category'));

        $query = Product::query()->active()->forCard()->has('activeVariants')->latest('id');

        if ($category) {
            $query->whereIn('category_id', $category->branchIds());
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

        return view('tma.catalog', [
            'products' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'categories' => $categories,
            'category' => $category,
            'search' => $search,
            'discounted' => $discounted,
        ]);
    }

    public function product(Product $product): View
    {
        abort_unless($product->isVisible(), 404);

        $product->load(['brand', 'unit', 'images', 'activeVariants']);

        abort_if($product->activeVariants->isEmpty(), 404);

        return view('tma.product', ['product' => $product]);
    }

    public function cart(CartService $cartService): View
    {
        $cart = $cartService->summary();

        return view('tma.cart', [
            'cart' => $cart,
            'slugs' => Product::query()->whereIn('id', array_column($cart['items'], 'product_id'))->pluck('slug', 'id'),
        ]);
    }
}
