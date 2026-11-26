<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandShowRequest;
use App\Models\Brand;
use App\Services\BrandService;
use App\Services\BrandStoryService;
use App\Services\PostService;
use App\Support\ProductSorting;
use Illuminate\Contracts\View\View;

class BrandController extends Controller
{
    public function __construct(
        private BrandService $brands,
        private PostService $posts,
        private BrandStoryService $stories,
    ) {}

    public function index(): View
    {
        return view('pages.brands.index', [
            'brands' => $this->brands->listing(),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Brands', 'url' => null],
            ],
        ]);
    }

    public function show(BrandShowRequest $request, Brand $brand): View
    {
        abort_unless($brand->is_active, 404);

        $sort = $request->sort();

        return view('pages.brands.show', [
            'brand' => $brand,
            'story' => $request->hasAny(['page', 'sort']) ? null : $this->stories->forPage($brand),
            'stats' => $this->brands->stats($brand),
            'products' => $this->brands->products($brand, $sort),
            'sort' => $sort,
            'sorts' => ProductSorting::OPTIONS,
            'posts' => $this->posts->latestForBrand($brand),
            'schema' => $this->brands->schema($brand),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Brands', 'url' => route('brands.index')],
                ['label' => $brand->name, 'url' => null],
            ],
        ]);
    }
}
