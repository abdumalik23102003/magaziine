<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function variant(array $attributes = []): ProductVariant
    {
        return ProductVariant::factory()
            ->for(Product::factory())
            ->create($attributes + ['price' => '10.00', 'discount_percentage' => null, 'stock' => 10, 'is_active' => true]);
    }

    private function guest(): static
    {
        return $this->withSession(['visitor_token' => 'test-token']);
    }

    public function test_guest_can_add_and_totals_use_discounted_prices(): void
    {
        $plain = $this->variant();
        $sale = $this->variant(['price' => '20.00', 'discount_percentage' => 25]);

        $this->guest()->postJson('/cart/items', ['variant_id' => $plain->id, 'quantity' => 2])->assertOk();

        $this->guest()->postJson('/cart/items', ['variant_id' => $sale->id])
            ->assertOk()
            ->assertJsonPath('cart.count', 3)
            ->assertJsonPath('cart.subtotal', '35.00')   // 2 x 10.00 + 1 x 15.00
            ->assertJsonPath('cart.qualifies', true);
    }

    public function test_adding_the_same_variant_increases_quantity(): void
    {
        $variant = $this->variant();

        $this->guest()->postJson('/cart/items', ['variant_id' => $variant->id])->assertOk();
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cart.count', 3);

        $this->assertSame(1, Cart::count());
    }

    public function test_cannot_exceed_stock(): void
    {
        $variant = $this->variant(['stock' => 3]);

        $this->guest()->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 5])
            ->assertStatus(422);

        $this->guest()->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 3])->assertOk();
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant->id])->assertStatus(422);
    }

    public function test_inactive_and_out_of_stock_variants_are_rejected(): void
    {
        $inactive = $this->variant(['is_active' => false]);
        $empty = $this->variant(['stock' => 0]);

        $this->guest()->postJson('/cart/items', ['variant_id' => $inactive->id])->assertStatus(422);
        $this->guest()->postJson('/cart/items', ['variant_id' => $empty->id])->assertStatus(422);
        $this->guest()->postJson('/cart/items', ['variant_id' => 999999])->assertStatus(422);
    }

    public function test_update_remove_and_clear(): void
    {
        $a = $this->variant();
        $b = $this->variant();

        $this->guest()->postJson('/cart/items', ['variant_id' => $a->id])->assertOk();
        $this->guest()->postJson('/cart/items', ['variant_id' => $b->id])->assertOk();

        $this->guest()->patchJson("/cart/items/{$a->id}", ['quantity' => 4])
            ->assertOk()->assertJsonPath('cart.count', 5);

        $this->guest()->deleteJson("/cart/items/{$b->id}")
            ->assertOk()->assertJsonPath('cart.count', 4);

        $this->guest()->deleteJson('/cart')
            ->assertOk()->assertJsonPath('cart.count', 0);
    }

    public function test_carts_are_separate_per_visitor(): void
    {
        $variant = $this->variant();

        $this->withSession(['visitor_token' => 'one'])->postJson('/cart/items', ['variant_id' => $variant->id])->assertOk();

        $this->withSession(['visitor_token' => 'two'])->getJson('/cart/summary')
            ->assertOk()->assertJsonPath('cart.count', 0);
    }

    public function test_stock_drop_clamps_the_cart_line(): void
    {
        $variant = $this->variant(['stock' => 5]);
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 5])->assertOk();

        $variant->update(['stock' => 2]);

        $this->guest()->getJson('/cart/summary')->assertJsonPath('cart.count', 2);

        $variant->update(['stock' => 0]);

        $this->guest()->getJson('/cart/summary')->assertJsonPath('cart.count', 0);
    }

    public function test_cart_page_and_drawer_state_render(): void
    {
        $variant = $this->variant();
        $this->guest()->postJson('/cart/items', ['variant_id' => $variant->id])->assertOk();

        $this->guest()->get('/cart')->assertOk()->assertSee($variant->product->name);
        $this->guest()->get('/')->assertOk()->assertSee('window.FoodMart', false);
    }

    public function test_non_json_form_post_redirects_back(): void
    {
        $variant = $this->variant();

        $this->guest()->from('/catalog')->post('/cart/items', ['variant_id' => $variant->id])
            ->assertRedirect('/catalog');
    }

    public function test_logged_in_user_has_their_own_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->variant();

        $this->actingAs($user)->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();

        $this->assertSame(2, (int) Cart::where('user_id', $user->id)->first()->items()->first()->quantity);
    }
}
