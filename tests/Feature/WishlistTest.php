<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $variant = []): Product
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create($variant + ['price' => '10.00', 'discount_percentage' => null, 'stock' => 10]);

        return $product;
    }

    private function guest(): static
    {
        return $this->withSession(['visitor_token' => 'wish-token']);
    }

    public function test_toggle_adds_and_removes(): void
    {
        $product = $this->product();

        $this->guest()->postJson("/wishlist/{$product->id}")
            ->assertOk()
            ->assertJsonPath('added', true)
            ->assertJsonPath('wishlist.count', 1);

        $this->guest()->postJson("/wishlist/{$product->id}")
            ->assertOk()
            ->assertJsonPath('added', false)
            ->assertJsonPath('wishlist.count', 0);
    }

    public function test_remove_and_clear(): void
    {
        $a = $this->product();
        $b = $this->product();

        $this->guest()->postJson("/wishlist/{$a->id}")->assertOk();
        $this->guest()->postJson("/wishlist/{$b->id}")->assertOk();

        $this->guest()->deleteJson("/wishlist/{$a->id}")->assertOk()->assertJsonPath('wishlist.count', 1);
        $this->guest()->deleteJson('/wishlist')->assertOk()->assertJsonPath('wishlist.count', 0);
    }

    public function test_inactive_product_cannot_be_saved(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->guest()->postJson("/wishlist/{$product->id}")->assertNotFound();
    }

    public function test_page_lists_saved_products(): void
    {
        $product = $this->product();
        $this->guest()->postJson("/wishlist/{$product->id}")->assertOk();

        $this->guest()->get('/wishlist')->assertOk()->assertSee($product->name);
    }

    public function test_move_all_to_cart(): void
    {
        $inStock = $this->product();
        $soldOut = $this->product(['stock' => 0]);

        $this->guest()->postJson("/wishlist/{$inStock->id}")->assertOk();
        $this->guest()->postJson("/wishlist/{$soldOut->id}")->assertOk();

        $this->guest()->postJson('/wishlist/move-to-cart')
            ->assertOk()
            ->assertJsonPath('cart.count', 1)
            ->assertJsonPath('wishlist.count', 1);
    }

    public function test_user_wishlist_is_stored_per_user(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->postJson("/wishlist/{$product->id}")->assertOk();

        $this->assertDatabaseHas('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
        $this->assertSame(1, WishlistItem::count());
    }
}
