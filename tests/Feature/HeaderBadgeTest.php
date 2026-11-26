<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function assertBadge(string $html, string $id, string $display, int $count): void
    {
        $this->assertMatchesRegularExpression(
            '~id="'.$id.'"[^>]*display: '.$display.';">'.$count.'</span>~',
            $html,
        );
    }

    public function test_badges_are_hidden_when_nothing_is_saved(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertBadge($html, 'nav-wishlist-count', 'none', 0);
        $this->assertBadge($html, 'nav-cart-count', 'none', 0);
    }

    public function test_badges_show_real_counts_in_the_first_html(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'stock' => 100,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());
        $this->postJson('/cart/items', ['variant_id' => $variant->id])->assertOk();
        $this->postJson("/wishlist/{$product->id}")->assertOk();

        $html = $this->get('/profile')->assertOk()->getContent();

        $this->assertBadge($html, 'nav-wishlist-count', 'flex', 1);
        $this->assertBadge($html, 'nav-cart-count', 'flex', 1);
    }
}
