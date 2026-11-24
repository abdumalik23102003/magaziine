<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use RefreshDatabase;

    private function product(Brand $brand, string $name, array $attributes = [], array $variant = []): Product
    {
        $product = Product::factory()->create(
            ['brand_id' => $brand->id, 'name' => $name, 'slug' => Str::slug($name)] + $attributes
        );

        ProductVariant::factory()->for($product)->create($variant + [
            'price' => '10.00',
            'discount_percentage' => null,
            'is_active' => true,
        ]);

        return $product;
    }

    private function admin(): User
    {
        $user = User::factory()->admin()->create();

        return $user;
    }

    public function test_brands_index_lists_only_active_brands(): void
    {
        Brand::factory()->create(['name' => 'Visible Farm', 'slug' => 'visible-farm']);
        Brand::factory()->inactive()->create(['name' => 'Hidden Farm', 'slug' => 'hidden-farm']);

        $this->get('/brands')
            ->assertOk()
            ->assertSee('Visible Farm')
            ->assertDontSee('Hidden Farm')
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_brands_index_is_paginated(): void
    {
        Brand::factory()->count(13)->create();

        $this->get('/brands')
            ->assertOk()
            ->assertViewHas('brands', fn ($brands) => $brands->count() === 12 && $brands->total() === 13)
            ->assertSee('page=2', false);

        $this->get('/brands?page=2')
            ->assertViewHas('brands', fn ($brands) => $brands->count() === 1);
    }

    public function test_brand_page_shows_hero_and_only_its_own_sellable_products(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Golden Orchard',
            'slug' => 'golden-orchard',
            'tagline' => 'Apples with attitude',
            'description' => 'Family orchard since 1950.',
            'website' => 'https://orchard.example.com',
        ]);
        $other = Brand::factory()->create(['name' => 'Other Co', 'slug' => 'other-co']);

        $this->product($brand, 'Orchard Apple');
        $this->product($other, 'Foreign Pear');
        $this->product($brand, 'Inactive Plum', ['is_active' => false]);
        $this->product($brand, 'Deleted Fig')->delete();
        Product::factory()->create(['brand_id' => $brand->id, 'name' => 'Variantless Kiwi', 'slug' => 'variantless-kiwi']);

        $this->get('/brands/golden-orchard')
            ->assertOk()
            ->assertSee('Golden Orchard')
            ->assertSee('Apples with attitude')
            ->assertSee('Family orchard since 1950.')
            ->assertSee('https://orchard.example.com', false)
            ->assertSee('Orchard Apple')
            ->assertDontSee('Foreign Pear')
            ->assertDontSee('Inactive Plum')
            ->assertDontSee('Deleted Fig')
            ->assertDontSee('Variantless Kiwi')
            ->assertViewHas('stats', fn ($stats) => $stats['products'] === 1);
    }

    public function test_brand_page_has_organization_and_breadcrumb_json_ld(): void
    {
        $brand = Brand::factory()->create(['name' => 'Schema Co', 'slug' => 'schema-co']);

        $this->get('/brands/schema-co')
            ->assertOk()
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('Brands');
    }

    public function test_inactive_or_unknown_brand_is_404(): void
    {
        Brand::factory()->inactive()->create(['slug' => 'dormant']);

        $this->get('/brands/dormant')->assertNotFound();
        $this->get('/brands/does-not-exist')->assertNotFound();
    }

    public function test_unsafe_website_scheme_is_not_rendered_as_a_link(): void
    {
        Brand::factory()->create(['slug' => 'sneaky', 'website' => 'javascript:alert(1)']);

        $this->get('/brands/sneaky')
            ->assertOk()
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('Visit website');
    }

    public function test_brand_page_sorting_and_pagination(): void
    {
        $brand = Brand::factory()->create(['slug' => 'sorted']);

        foreach (range(1, 14) as $i) {
            $this->product($brand, "Item {$i}", [], ['price' => (string) (10 + $i).'.00']);
        }
        $this->product($brand, 'Bargain', [], ['price' => '40.00', 'discount_percentage' => 90]);

        $this->get('/brands/sorted')
            ->assertViewHas('products', fn ($p) => $p->count() === 12 && $p->total() === 15);

        $this->get('/brands/sorted?sort=price_asc')
            ->assertViewHas('products', fn ($p) => $p->getCollection()->first()->name === 'Bargain');

        $this->get('/brands/sorted?sort=price_desc')
            ->assertViewHas('products', fn ($p) => $p->getCollection()->first()->name === 'Item 14');

        $this->get('/brands/sorted?sort[]=x&page=abc')->assertOk();
        $this->get('/brands/sorted?sort=drop-table')->assertOk()->assertViewHas('sort', 'latest');
    }

    public function test_carousel_only_shows_featured_and_active_brands_in_order(): void
    {
        Brand::factory()->featured()->create(['name' => 'Second', 'slug' => 'second', 'sort_order' => 2]);
        Brand::factory()->featured()->create(['name' => 'First', 'slug' => 'first', 'sort_order' => 1]);
        Brand::factory()->create(['name' => 'Plain', 'slug' => 'plain']);
        Brand::factory()->featured()->inactive()->create(['name' => 'Dormant', 'slug' => 'dormant']);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('brandCarousel', fn ($carousel) => collect($carousel)->pluck('slug')->all() === ['first', 'second'])
            ->assertSee('data-brand-carousel', false)
            ->assertSee('Featured Brands')
            ->assertSee(route('brands.show', 'first'), false)
            ->assertDontSee(route('brands.show', 'plain'), false);
    }

    public function test_carousel_is_hidden_without_featured_brands(): void
    {
        Brand::factory()->create();

        $this->get('/')->assertOk()->assertDontSee('data-brand-carousel', false);
    }

    public function test_carousel_counts_only_sellable_products(): void
    {
        $brand = Brand::factory()->featured()->create(['slug' => 'counted']);

        $this->product($brand, 'One');
        $this->product($brand, 'Two');
        $this->product($brand, 'Off', ['is_active' => false]);
        $this->product($brand, 'Gone')->delete();

        $this->get('/')->assertViewHas('brandCarousel', fn ($c) => $c[0]['products_count'] === 2);
    }

    public function test_carousel_cache_is_flushed_when_a_brand_changes(): void
    {
        $brand = Brand::factory()->featured()->create(['name' => 'Old Name', 'slug' => 'cached']);

        $this->get('/')->assertSee('Old Name');

        $brand->update(['name' => 'Fresh Name']);

        $this->get('/')->assertSee('Fresh Name')->assertDontSee('Old Name');

        $brand->update(['is_active' => false]);

        $this->get('/')->assertDontSee('Fresh Name');
    }

    public function test_catalog_brand_filter(): void
    {
        $acme = Brand::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
        $other = Brand::factory()->create(['name' => 'Other', 'slug' => 'other']);

        $this->product($acme, 'Acme Jam');
        $this->product($other, 'Other Jam');

        $this->get('/catalog?brand=acme')
            ->assertOk()
            ->assertSee('Acme Jam')
            ->assertDontSee('Other Jam');
    }

    public function test_catalog_ignores_inactive_or_garbage_brand_filter(): void
    {
        $dormant = Brand::factory()->inactive()->create(['slug' => 'dormant']);
        $this->product($dormant, 'Dormant Jam');

        $this->get('/catalog?brand=dormant')->assertOk()->assertViewHas('brand', null);
        $this->get('/catalog?brand[]=x')->assertOk();
    }

    public function test_media_urls(): void
    {
        $bundled = Brand::factory()->make(['logo_path' => 'foodmart/images/thumb-milk.png', 'cover_path' => null]);
        $uploaded = Brand::factory()->make(['logo_path' => 'brands/logos/a.png']);

        $this->assertSame(asset('foodmart/images/thumb-milk.png'), $bundled->logo_url);
        $this->assertNull($bundled->cover_url);
        $this->assertSame(Storage::disk('public')->url('brands/logos/a.png'), $uploaded->logo_url);
    }

    public function test_replacing_a_logo_deletes_the_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('brands/logos/old.png', 'x');
        Storage::disk('public')->put('brands/logos/new.png', 'y');

        $brand = Brand::factory()->create(['logo_path' => 'brands/logos/old.png']);
        $brand->update(['logo_path' => 'brands/logos/new.png']);

        Storage::disk('public')->assertMissing('brands/logos/old.png');
        Storage::disk('public')->assertExists('brands/logos/new.png');
    }

    public function test_admin_can_manage_brands(): void
    {
        $brand = Brand::factory()->create();

        $this->actingAs($this->admin());

        $this->get('/admin/brands')->assertOk();
        $this->get('/admin/brands/create')->assertOk();
        $this->get("/admin/brands/{$brand->getRouteKey()}/edit")->assertOk();
    }

    public function test_customer_cannot_open_brand_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/brands')->assertForbidden();
    }
}
