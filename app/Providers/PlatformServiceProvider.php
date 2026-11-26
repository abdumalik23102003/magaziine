<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Observers\ModerationNotificationObserver;
use App\Observers\NavigationObserver;
use App\Services\NavigationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([User::class, Order::class, Product::class, Post::class] as $model) {
            $model::observe(AuditObserver::class);
        }

        Product::observe(ModerationNotificationObserver::class);
        Post::observe(ModerationNotificationObserver::class);

        $conversation = (string) config('audit.conversation_model');

        if (class_exists($conversation)) {
            $conversation::observe(AuditObserver::class);
            $conversation::observe(ModerationNotificationObserver::class);
        }

        foreach ([Category::class, Brand::class, Post::class, Product::class] as $model) {
            $model::observe(NavigationObserver::class);
        }

        View::composer('partials.nav-dropdowns', function ($view): void {
            $navigation = app(NavigationService::class);

            $view->with([
                'navPages' => $navigation->pages(auth()->check()),
                'navCatalog' => $navigation->catalog(),
            ]);
        });
    }
}
