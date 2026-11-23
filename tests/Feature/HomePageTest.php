<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_active_product_with_variant(): void
    {
        $product = Product::factory()->create(['name' => 'Test Avocado']);
        ProductVariant::factory()->for($product)->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('Test Avocado');
    }

    public function test_inactive_product_is_hidden(): void
    {
        $product = Product::factory()->create(['name' => 'Hidden Mango', 'is_active' => false]);
        ProductVariant::factory()->for($product)->create();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Hidden Mango');
    }

    public function test_discounted_variant_final_price_is_calculated(): void
    {
        $variant = ProductVariant::factory()->create([
            'price' => '10.00',
            'discount_percentage' => 25,
        ]);

        $this->assertSame('7.50', $variant->final_price);
        $this->assertTrue($variant->has_discount);
    }

    public function test_home_page_links_cards_and_categories(): void
    {
        $category = Category::factory()->create(['name' => 'Fruits', 'slug' => 'fruits']);
        $product = Product::factory()->create(['category_id' => $category->id, 'slug' => 'linked-product']);
        ProductVariant::factory()->for($product)->create();

        $this->get('/')
            ->assertOk()
            ->assertSee(route('products.show', 'linked-product'), false)
            ->assertSee(route('catalog.category', 'fruits'), false);
    }
}
