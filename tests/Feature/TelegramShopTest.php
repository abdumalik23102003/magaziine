<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramShopTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['geocoding.require_pin' => false]);

        $this->user = User::factory()->create(['name' => 'Telegram Buyer']);
        $this->user->forceFill(['telegram_id' => 555])->save();
    }

    private function variant(array $product = [], array $variant = []): ProductVariant
    {
        return ProductVariant::factory()
            ->for(Product::factory()->create($product))
            ->create($variant + ['name' => '1 kg', 'price' => '10.00', 'discount_percentage' => null, 'stock' => 5]);
    }

    /** @return array<string, string> */
    private function checkoutData(): array
    {
        return [
            'name' => 'Ali Valiyev',
            'phone' => '+998 90 123 45 67',
            'city' => 'Tashkent',
            'address' => 'Amir Temur 1',
            'delivery_method' => 'express',
            'payment_method' => 'cod',
        ];
    }

    public function test_every_shop_page_requires_telegram_sign_in(): void
    {
        foreach (['/tma', '/tma/cart', '/tma/checkout', '/tma/orders'] as $url) {
            $this->get($url)->assertRedirect(route('tma.login'));
        }

        $this->post('/tma/checkout', $this->checkoutData())->assertRedirect(route('tma.login'));
    }

    public function test_catalog_lists_visible_products_and_filters_them(): void
    {
        $fruits = Category::factory()->create(['name' => 'Fruits', 'slug' => 'fruits']);
        $this->variant(['name' => 'Red Apple', 'slug' => 'red-apple', 'category_id' => $fruits->id]);
        $this->variant(['name' => 'Rye Bread', 'slug' => 'rye-bread']);
        $this->variant(['name' => 'Hidden Mango', 'slug' => 'hidden-mango', 'is_active' => false]);

        $this->actingAs($this->user)->get('/tma')
            ->assertOk()
            ->assertSee('Red Apple')
            ->assertSee('Rye Bread')
            ->assertDontSee('Hidden Mango')
            ->assertSee(route('tma.products.show', 'red-apple'), false)
            ->assertSee('id="tma-cart-count"', false);

        $this->get('/tma?category=fruits')->assertSee('Red Apple')->assertDontSee('Rye Bread');
        $this->get('/tma?q=rye')->assertSee('Rye Bread')->assertDontSee('Red Apple');
        $this->get('/tma?q[]=x&category[]=y')->assertOk();
    }

    public function test_product_page_shows_sizes_and_hides_inactive_products(): void
    {
        $variant = $this->variant(['name' => 'Red Apple', 'slug' => 'red-apple']);
        $this->variant(['slug' => 'off', 'is_active' => false]);

        $this->actingAs($this->user)->get('/tma/products/red-apple')
            ->assertOk()
            ->assertSee('1 kg')
            ->assertSee('value="'.$variant->id.'"', false)
            ->assertSee('Add to cart');

        $this->get('/tma/products/off')->assertNotFound();
    }

    public function test_cart_page_shows_added_items(): void
    {
        $variant = $this->variant(['name' => 'Red Apple', 'slug' => 'red-apple']);

        $this->actingAs($this->user)
            ->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cart.count', 2);

        $this->get('/tma/cart')
            ->assertOk()
            ->assertSee('Red Apple')
            ->assertSee('$20.00')
            ->assertSee(route('tma.checkout'), false);
    }

    public function test_empty_cart_cannot_open_checkout(): void
    {
        $this->actingAs($this->user)->get('/tma/checkout')->assertRedirect(route('tma.home'));
    }

    public function test_checkout_places_an_order_and_stays_in_the_mini_app(): void
    {
        $variant = $this->variant(['name' => 'Red Apple']);
        $this->actingAs($this->user)->postJson('/cart/items', ['variant_id' => $variant->id, 'quantity' => 2])->assertOk();

        $this->get('/tma/checkout')->assertOk()->assertSee('Place order');

        $response = $this->post('/tma/checkout', $this->checkoutData());
        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('tma.orders.show', $order));
        $this->assertSame($this->user->id, $order->user_id);
        $this->assertSame(3.0, (float) $variant->refresh()->stock);
        $this->getJson('/cart/summary')->assertJsonPath('cart.count', 0);

        $this->get(route('tma.orders.show', $order))->assertOk()->assertSee($order->number)->assertSee('Red Apple');
        $this->get('/tma/orders')->assertOk()->assertSee($order->number);
    }

    public function test_invalid_checkout_returns_to_the_form(): void
    {
        $variant = $this->variant();
        $this->actingAs($this->user)->postJson('/cart/items', ['variant_id' => $variant->id])->assertOk();

        $this->from('/tma/checkout')
            ->post('/tma/checkout', ['name' => 'Ali'])
            ->assertRedirect('/tma/checkout')
            ->assertSessionHasErrors(['phone', 'city', 'address']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_orders_of_other_users_are_hidden_and_own_order_can_be_cancelled(): void
    {
        $variant = $this->variant();
        $this->actingAs($this->user)->postJson('/cart/items', ['variant_id' => $variant->id])->assertOk();
        $this->post('/tma/checkout', $this->checkoutData())->assertRedirect();
        $order = Order::query()->firstOrFail();

        $other = User::factory()->create();
        $other->forceFill(['telegram_id' => 556])->save();

        $this->actingAs($other)->get(route('tma.orders.show', $order))->assertNotFound();
        $this->get('/tma/orders')->assertOk()->assertSee('No orders yet.')->assertDontSee(route('tma.orders.show', $order), false);

        $this->actingAs($this->user)
            ->from(route('tma.orders.show', $order))
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('tma.orders.show', $order));

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }
}
