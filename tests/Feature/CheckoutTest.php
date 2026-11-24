<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'name' => 'Ali Valiyev',
        'phone' => '+998 90 123 45 67',
        'city' => 'Tashkent',
        'address' => 'Amir Temur 10',
        'apartment' => '12',
        'delivery_method' => 'morning',
        'payment_method' => 'cod',
        'note' => 'Call first',
    ];

    private function cartWith(User $user, string $price, int $qty, int $stock = 10, ?int $discount = null): ProductVariant
    {
        $variant = ProductVariant::factory()
            ->for(Product::factory())
            ->create(['price' => $price, 'stock' => $stock, 'discount_percentage' => $discount, 'is_active' => true]);

        $cart = Cart::create(['user_id' => $user->id]);
        $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => $qty]);

        return $variant;
    }

    public function test_empty_cart_redirects_to_catalog(): void
    {
        $this->actingAs(User::factory()->create())->get('/checkout')->assertRedirect('/catalog');
    }

    public function test_checkout_page_renders_with_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->cartWith($user, '10.00', 2);

        $this->actingAs($user)->get('/checkout')
            ->assertOk()
            ->assertSee($variant->product->name)
            ->assertSee('Place order');
    }

    public function test_order_is_created_stock_reduced_and_cart_cleared(): void
    {
        $user = User::factory()->create();
        $variant = $this->cartWith($user, '10.00', 2);

        $response = $this->actingAs($user)->post('/checkout', $this->form);

        $order = Order::first();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('20.00', $order->subtotal);
        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('20.00', $order->total_price);
        $this->assertSame('Amir Temur 10, 12, Tashkent', $order->address);

        $item = $order->items()->first();
        $this->assertSame($variant->product->name, $item->product_name);
        $this->assertSame('10.00', $item->price);
        $this->assertSame(2, (int) $item->quantity);

        $this->assertSame(8, (int) $variant->fresh()->stock);
        $this->assertSame(0, Cart::where('user_id', $user->id)->first()->items()->count());
    }

    public function test_delivery_fee_is_charged_below_the_free_threshold_and_waived_above(): void
    {
        $small = User::factory()->create();
        $this->cartWith($small, '10.00', 1);
        $this->actingAs($small)->post('/checkout', ['delivery_method' => 'express'] + $this->form);
        $this->assertSame('13.99', Order::where('user_id', $small->id)->first()->total_price);

        $big = User::factory()->create();
        $this->cartWith($big, '40.00', 1);
        $this->actingAs($big)->post('/checkout', ['delivery_method' => 'express'] + $this->form);
        $this->assertSame('40.00', Order::where('user_id', $big->id)->first()->total_price);
    }

    public function test_discount_is_applied_to_the_order(): void
    {
        $user = User::factory()->create();
        $this->cartWith($user, '20.00', 1, 10, 50);

        $this->actingAs($user)->post('/checkout', $this->form);

        $this->assertSame('10.00', Order::first()->total_price);
    }

    public function test_validation_errors(): void
    {
        $user = User::factory()->create();
        $this->cartWith($user, '10.00', 1);

        $this->actingAs($user)->post('/checkout', ['phone' => 'abc', 'delivery_method' => 'teleport'] + $this->form)
            ->assertSessionHasErrors(['phone', 'delivery_method']);

        $this->assertSame(0, Order::count());
    }

    public function test_cancelling_a_pending_order_restores_stock(): void
    {
        $user = User::factory()->create();
        $variant = $this->cartWith($user, '10.00', 3);

        $this->actingAs($user)->post('/checkout', $this->form);
        $order = Order::first();
        $this->assertSame(7, (int) $variant->fresh()->stock);

        $this->actingAs($user)->post(route('orders.cancel', $order))->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(10, (int) $variant->fresh()->stock);

        // ikkinchi marta bekor qilish qoldiqni yana oshirmaydi
        $this->actingAs($user)->post(route('orders.cancel', $order));
        $this->assertSame(10, (int) $variant->fresh()->stock);
    }

    public function test_shipped_order_cannot_be_cancelled_by_the_customer(): void
    {
        $user = User::factory()->create();
        $this->cartWith($user, '10.00', 1);
        $this->actingAs($user)->post('/checkout', $this->form);

        $order = Order::first();
        $order->update(['status' => OrderStatus::Shipped]);

        $this->actingAs($user)->post(route('orders.cancel', $order));

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }

    public function test_orders_are_private(): void
    {
        $owner = User::factory()->create();
        $this->cartWith($owner, '10.00', 1);
        $this->actingAs($owner)->post('/checkout', $this->form);
        $order = Order::first();

        $this->actingAs($owner)->get(route('orders.show', $order))->assertOk()->assertSee($order->number);

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('orders.show', $order))->assertNotFound();
        $this->actingAs($stranger)->post(route('orders.cancel', $order))->assertNotFound();
    }

    public function test_reorder_fills_the_cart(): void
    {
        $user = User::factory()->create();
        $variant = $this->cartWith($user, '10.00', 2);
        $this->actingAs($user)->post('/checkout', $this->form);

        $this->actingAs($user)->post(route('orders.reorder', Order::first()))->assertRedirect(route('cart.show'));

        $this->assertSame(2, (int) Cart::where('user_id', $user->id)->first()->items()->first()->quantity);
    }
}
