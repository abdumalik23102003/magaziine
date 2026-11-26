<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NavigationDropdownsTest extends TestCase
{
    use RefreshDatabase;

    private function sellable(Category $category, array $attributes = []): Product
    {
        $product = Product::factory()->create(['category_id' => $category->id] + $attributes);
        ProductVariant::factory()->for($product)->create(['is_active' => true]);

        return $product;
    }

    public function test_pages_differ_for_guests_and_authenticated_users(): void
    {
        $this->get('/')->assertOk()->assertSee('Sahifalar')->assertSee('Katalog')->assertSee('Kirish')->assertDontSee('Buyurtmalarim');

        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertSee('Buyurtmalarim')->assertDontSee('Kirish');
    }

    public function test_catalog_dropdown_shows_the_category_tree_featured_brands_and_deals(): void
    {
        $parent = Category::factory()->create(['name' => 'Fruits', 'slug' => 'fruits']);
        $child = Category::factory()->create(['name' => 'Berries', 'slug' => 'berries', 'parent_id' => $parent->id]);
        Category::factory()->create(['name' => 'Empty Aisle', 'slug' => 'empty-aisle']);
        $this->sellable($child);
        Brand::factory()->featured()->create(['name' => 'Fresh Farm', 'slug' => 'fresh-farm']);
        Brand::factory()->featured()->inactive()->create(['name' => 'Hidden Farm', 'slug' => 'hidden-farm']);

        Cache::flush();

        $this->get('/')
            ->assertSee(route('catalog.category', 'fruits'), false)
            ->assertSee(route('catalog.category', 'berries'), false)
            ->assertSee('Fresh Farm')
            ->assertDontSee('Hidden Farm')
            ->assertSee(route('brands.index'), false)
            ->assertSee('discounted=1', false);

        $names = array_column(app(NavigationService::class)->catalog()['categories'], 'name');

        $this->assertContains('Fruits', $names);
        $this->assertNotContains('Empty Aisle', $names);
    }

    public function test_categories_with_only_pending_products_are_hidden(): void
    {
        $category = Category::factory()->create(['name' => 'Pending Aisle', 'slug' => 'pending-aisle']);
        $product = $this->sellable($category);
        $product->forceFill(['approval_status' => 'pending'])->save();

        Cache::flush();

        $names = array_column(app(NavigationService::class)->catalog()['categories'], 'name');

        $this->assertNotContains('Pending Aisle', $names);
    }

    public function test_cache_is_flushed_when_a_brand_changes(): void
    {
        $brand = Brand::factory()->featured()->create(['name' => 'Old Name', 'slug' => 'cached-brand']);

        $this->get('/')->assertSee('Old Name');

        $brand->update(['name' => 'New Name']);

        $this->get('/')->assertSee('New Name')->assertDontSee('Old Name');
    }

    public function test_names_are_escaped(): void
    {
        Brand::factory()->featured()->create(['name' => '<script>alert(1)</script>', 'slug' => 'xss-brand']);

        Cache::flush();

        $this->get('/')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_catalog_data_is_built_with_few_queries(): void
    {
        Cache::flush();
        DB::enableQueryLog();

        app(NavigationService::class)->catalog();

        $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
    }
}
