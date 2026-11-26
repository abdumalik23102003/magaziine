<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CommentService;
use App\Services\ProductViewService;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        // O'chirilgan (soft delete) mahsulotni route binding o'zi 404 qiladi.
        abort_unless($product->isVisible(), 404);

        $product->load(['category.parent', 'brand', 'unit', 'images', 'activeVariants']);

        abort_if($product->activeVariants->isEmpty(), 404);
        app(ProductViewService::class)->record($product, request());

        return view('pages.product', [
            'product' => $product,
            'related' => $this->related($product),
            'breadcrumbs' => $this->breadcrumbs($product),
            'discussion' => app(CommentService::class)->discussion($product, request()),
        ]);
    }

    private function related(Product $product)
    {
        $base = fn () => Product::query()
            ->active()
            ->forCard()
            ->with('brand')
            ->has('activeVariants');

        $related = $base()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->latest('id')
            ->take(4)
            ->get();

        // Kategoriyada 4 tadan kam bo'lsa, boshqa mahsulotlar bilan to'ldiramiz.
        if ($related->count() < 4) {
            $exclude = $related->pluck('id')->push($product->getKey())->all();

            $related = $related->concat(
                $base()->whereNotIn('id', $exclude)->latest('id')->take(4 - $related->count())->get()
            );
        }

        return $related;
    }

    private function breadcrumbs(Product $product): array
    {
        $crumbs = [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Catalog', 'url' => route('catalog.index')],
        ];

        foreach ($product->category->trail() as $node) {
            $crumbs[] = ['label' => $node->name, 'url' => route('catalog.category', $node->slug)];
        }

        $crumbs[] = ['label' => $product->name, 'url' => null];

        return $crumbs;
    }
}
