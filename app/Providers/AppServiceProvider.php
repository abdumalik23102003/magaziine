<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Observers\PartnerChangeObserver;
use App\Services\BrandStoryService;
use App\Services\CartService;
use App\Services\Geocoding\CachingGeocoder;
use App\Services\Geocoding\Geocoder;
use App\Services\Geocoding\NominatimGeocoder;
use App\Services\WishlistService;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Geokodlash provayderi (config/geocoding.php). Almashtirish: yangi Geocoder yozing va match ga qo'shing.
        $this->app->singleton(Geocoder::class, function ($app) {
            $driver = (string) config('geocoding.driver');

            $inner = match ($driver) {
                'nominatim' => new NominatimGeocoder((array) config('geocoding.nominatim')),
                default => throw new \InvalidArgumentException("Unknown geocoding driver [{$driver}]."),
            };

            return new CachingGeocoder(
                $inner,
                $app->make('cache.store'),
                (int) config('geocoding.cache_ttl'),
                (int) config('geocoding.cache_miss_ttl'),
                'geocoding:'.$driver,
            );
        });

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

        // Tunnel yoki reverse proxy orqasida havolalar https bo'lib chiqsin (config/telegram.php).
        if ($proxies = config('telegram.trusted_proxies')) {
            TrustProxies::at($proxies);
        }

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
