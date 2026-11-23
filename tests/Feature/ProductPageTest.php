<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function productWithVariants(array $attributes = []): Product
    {
        $product = Product::factory()->create($attributes + ['slug' => 'test-avocado', 'name' => 'Test Avocado']);

        ProductVariant::factory()->for($product)->create(['name' => '4-Pack', 'price' => '10.00', 'discount_percentage' => 25]);
        ProductVariant::factory()->for($product)->create(['name' => '8-Pack', 'price' => '18.00', 'discount_percentage' => null]);

        return $product;
    }

    public function test_product_page_shows_variants_and_breadcrumbs(): void
    {
        $parent = Category::factory()->create(['name' => 'Fruits', 'slug' => 'fruits']);
        $child = Category::factory()->create(['name' => 'Berries', 'slug' => 'berries', 'parent_id' => $parent->id]);
        $product = $this->productWithVariants(['category_id' => $child->id]);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Test Avocado')
            ->assertSee($product->brand->name)
            ->assertSee('4-Pack')
            ->assertSee('8-Pack')
            ->assertSee('-25%')
            ->assertSee('Fruits')
            ->assertSee('Berries')
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_inactive_product_is_404(): void
    {
        $product = $this->productWithVariants(['is_active' => false]);

        $this->get(route('products.show', $product->slug))->assertNotFound();
    }

    public function test_deleted_product_is_404(): void
    {
        $product = $this->productWithVariants();
        $product->delete();

        $this->get('/products/test-avocado')->assertNotFound();
    }

    public function test_product_without_active_variants_is_404(): void
    {
        $product = Product::factory()->create(['slug' => 'empty-one']);

        $this->get(route('products.show', $product->slug))->assertNotFound();
    }

    public function test_unknown_slug_is_404(): void
    {
        $this->get('/products/nope')->assertNotFound();
    }

    public function test_related_products_come_from_the_same_category(): void
    {
        $category = Category::factory()->create(['name' => 'Fruits', 'slug' => 'fruits']);
        $product = $this->productWithVariants(['category_id' => $category->id]);

        $sibling = Product::factory()->create(['name' => 'Sibling Pear', 'slug' => 'sibling-pear', 'category_id' => $category->id]);
        ProductVariant::factory()->for($sibling)->create();

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('More from Fruits')
            ->assertSee('Sibling Pear');
    }
}
