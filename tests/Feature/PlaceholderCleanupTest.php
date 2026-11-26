<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceholderCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_no_template_leftovers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Downtown Chicago')
            ->assertDontSee('Weekly Fresh Club')
            ->assertDontSee('href="#"', false);
    }

    public function test_catalog_cards_do_not_show_fake_stars(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create();

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('bi-star-fill', false);
    }

    public function test_product_page_has_no_fake_claims_and_wires_buttons(): void
    {
        $product = Product::factory()->create(['slug' => 'real-product']);
        ProductVariant::factory()->for($product)->create();

        $related = Product::factory()->create(['category_id' => $product->category_id]);
        $relatedVariant = ProductVariant::factory()->for($related)->create();

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertDontSee('Julian Vance')
            ->assertDontSee('Santa Barbara')
            ->assertDontSee('Vault Lot')
            ->assertDontSee('Members viewing')
            ->assertDontSee('Demeter')
            ->assertSee('data-product-id="'.$product->id.'"', false)
            ->assertSee('data-variant-id="'.$relatedVariant->id.'"', false);
    }
}
