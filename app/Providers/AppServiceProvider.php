<?php

namespace App\Providers;

use App\Models\Category;
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

        View::composer(
            ['partials.header', 'partials.footer', 'pages.catalog'],
            fn ($view) => $view->with('navCategories', app('nav.categories')),
        );
    }
}
