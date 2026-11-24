<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_logs_in(): void
    {
        $this->post('/register', [
            'name' => 'Ali Valiyev',
            'email' => 'ali@example.test',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ali@example.test', 'is_admin' => false]);
    }

    public function test_register_validation(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->post('/register', [
            'name' => '',
            'email' => 'taken@example.test',
            'password' => 'short',
            'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_cannot_make_yourself_admin_through_registration(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.test',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'is_admin' => 1,
        ]);

        $this->assertFalse((bool) User::where('email', 'sneaky@example.test')->first()->is_admin);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'login@example.test']);

        $this->post('/login', ['email' => 'login@example.test', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'login@example.test']);

        $this->post('/login', ['email' => 'login@example.test', 'password' => 'nope'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_cart_and_wishlist_are_merged_on_login(): void
    {
        $user = User::factory()->create(['email' => 'merge@example.test']);
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 10, 'is_active' => true]);

        $session = ['visitor_token' => 'merge-token'];

        $this->withSession($session)->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();
        $this->withSession($session)->postJson("/wishlist/{$product->id}")->assertOk();

        $this->withSession($session)->post('/login', ['email' => 'merge@example.test', 'password' => 'password'])
            ->assertRedirect('/');

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
        $this->assertSame(2, (int) $cart->items()->first()->quantity);
        $this->assertDatabaseMissing('carts', ['session_id' => 'merge-token']);
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
        $this->assertSame(1, WishlistItem::count());
    }

    public function test_protected_pages_redirect_guests_to_login(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
    }
}
