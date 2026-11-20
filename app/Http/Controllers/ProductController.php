<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $featured = Product::active()
            ->forCard()
            ->has('activeVariants')
            ->latest()
            ->take(8)
            ->get();

        $discounted = Product::active()
            ->forCard()
            ->whereHas('activeVariants', fn ($q) => $q->where('discount_percentage', '>', 0))
            ->latest()
            ->take(4)
            ->get();

        return view('pages.home', compact('featured', 'discounted'));
    }
}
