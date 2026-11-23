<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(string $name, string $price = '10.00', ?int $discount = null, array $attributes = []): Product
    {
        $product = Product::factory()->create(['name' => $name, 'slug' => Str::slug($name)] + $attributes);

        ProductVariant::factory()->for($product)->create([
            'price' => $price,
            'discount_percentage' => $discount,
        ]);

        return $product;
    }

    public function test_catalog_is_paginated(): void
    {
        foreach (range(1, 15) as $i) {
            $this->makeProduct("Product {$i}");
        }

        $this->get('/catalog')
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->count() === 12 && $p->total() === 15)
            ->assertSee('page=2', false);

        $this->get('/catalog?page=2')
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->count() === 3);
    }

    public function test_search_filters_by_name(): void
    {
        $this->makeProduct('Green Apple');
        $this->makeProduct('Blue Milk');

        $this->get('/catalog?q=apple')
            ->assertOk()
            ->assertSee('Green Apple')
            ->assertDontSee('Blue Milk');
    }

    public function test_search_text_is_escaped(): void
    {
        $this->get('/catalog?q='.urlencode('<script>alert(1)</script>'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_empty_result_shows_message(): void
    {
        $this->makeProduct('Green Apple');

        $this->get('/catalog?q=zzzz')
            ->assertOk()
            ->assertSee('Nothing found');
    }

    public function test_category_page_includes_child_categories_and_breadcrumbs(): void
    {
        $parent = Category::factory()->create(['name' => 'Fruits', 'slug' => 'fruits']);
        $child = Category::factory()->create(['name' => 'Berries', 'slug' => 'berries', 'parent_id' => $parent->id]);
        $other = Category::factory()->create(['name' => 'Drinks', 'slug' => 'drinks']);

        $this->makeProduct('Strawberry', attributes: ['category_id' => $child->id]);
        $this->makeProduct('Cola', attributes: ['category_id' => $other->id]);

        $this->get('/categories/fruits')
            ->assertOk()
            ->assertSee('Strawberry')
            ->assertDontSee('Cola');

        $this->get('/categories/berries')
            ->assertOk()
            ->assertSee('Strawberry')
            ->assertSee('Fruits')
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_unknown_category_is_404(): void
    {
        $this->get('/categories/does-not-exist')->assertNotFound();
    }

    public function test_brand_filter(): void
    {
        $acme = Brand::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
        $other = Brand::factory()->create(['name' => 'Other', 'slug' => 'other']);

        $this->makeProduct('Acme Jam', attributes: ['brand_id' => $acme->id]);
        $this->makeProduct('Other Jam', attributes: ['brand_id' => $other->id]);

        $this->get('/catalog?brand=acme')
            ->assertOk()
            ->assertSee('Acme Jam')
            ->assertDontSee('Other Jam');
    }

    public function test_discounted_filter(): void
    {
        $this->makeProduct('Sale Item', '10.00', 20);
        $this->makeProduct('Full Price Item');

        $this->get('/catalog?discounted=1')
            ->assertOk()
            ->assertSee('Sale Item')
            ->assertDontSee('Full Price Item');
    }

    public function test_sorting_by_price_uses_the_discounted_price(): void
    {
        $this->makeProduct('Pricey', '30.00');
        $this->makeProduct('Mid', '20.00');
        $this->makeProduct('Cheap', '15.00');
        $this->makeProduct('Bargain', '24.00', 50); // 12.00 after discount

        $this->get('/catalog?sort=price_asc')
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->getCollection()->pluck('name')->all() === ['Bargain', 'Cheap', 'Mid', 'Pricey']);

        $this->get('/catalog?sort=price_desc')
            ->assertViewHas('products', fn ($p) => $p->getCollection()->pluck('name')->all() === ['Pricey', 'Mid', 'Cheap', 'Bargain']);
    }

    public function test_garbage_query_values_do_not_break_the_page(): void
    {
        $this->makeProduct('Green Apple');

        $this->get('/catalog?q[]=x&sort[]=y&brand[]=z&discounted[]=1&page=abc')->assertOk();
        $this->get('/catalog?sort=drop-table&brand=nope')->assertOk()->assertSee('Green Apple');
    }
}
