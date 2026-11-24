<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    /** Savat, sevimlilar va kursor JS i faqat layouts.app da bor: barcha sahifa shuni ishlatsin. */
    public function test_every_storefront_page_uses_the_main_layout(): void
    {
        $product = Product::factory()->create(['slug' => 'layout-check']);
        ProductVariant::factory()->for($product)->create();

        foreach (['/', '/catalog', '/products/layout-check', '/cart', '/wishlist'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('js/core.js', false)
                ->assertSee('js/cart.js', false)
                ->assertSee('id="cart-toggle-btn"', false)
                ->assertSee('name="csrf-token"', false);
        }
    }

    public function test_users_table_has_role_columns_and_no_legacy_flag(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['role', 'is_active']));
        $this->assertFalse(Schema::hasColumn('users', 'is_admin'));
        $this->assertTrue(Schema::hasTable('brand_user'));
    }
}
