<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\BrandService;
use App\Services\PostService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(BrandService $brands, PostService $postService): View
    {
        $featured = Product::query()
            ->active()
            ->forCard()
            ->has('activeVariants')
            ->latest('id')
            ->take(8)
            ->get();

        $discounted = Product::query()
            ->active()
            ->forCard()
            ->whereHas('activeVariants', fn ($query) => $query->where('discount_percentage', '>', 0))
            ->latest('id')
            ->take(4)
            ->get();

        $categories = Category::rootsWithCounts();
        $brandCarousel = $brands->carousel();
        $posts = $postService->latest();

        return view('pages.home', compact('featured', 'discounted', 'categories', 'brandCarousel', 'posts'));
    }
}
