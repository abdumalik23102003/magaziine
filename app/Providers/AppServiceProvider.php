<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Observers\PartnerChangeObserver;
use App\Services\BrandStoryService;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Har so'rovda bir marta olinadi (header, footer va katalog bir xil ma'lumotni ishlatadi).
        $this->app->scoped('nav.categories', fn () => Category::query()
            ->roots()
            ->with('children:id,parent_id,name,slug')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug']));
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.neon');

        // Livewire vaqtinchalik yuklash limiti standart 12 MB: story video uchun oshiriladi.
        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.BrandStoryService::MAX_KB]]);

        // Hamkor mahsulotining muhim maydonlari o'zgarsa, qayta moderatsiyaga tushadi (config/partner.php).
        foreach ([Product::class, ProductVariant::class, ProductImage::class] as $model) {
            $model::observe(PartnerChangeObserver::class);
        }

        View::composer(
            ['partials.header', 'partials.footer', 'pages.catalog'],
            fn ($view) => $view->with('navCategories', app('nav.categories')),
        );

        // Layout: savat va sevimlilarning joriy holati (drawer'lar va header nishonlari uchun)
        View::composer('layouts.app', function ($view): void {
            $view->with([
                'cartData' => app(CartService::class)->summary(),
                'wishlistData' => app(WishlistService::class)->summary(),
            ]);
        });
    }
}
