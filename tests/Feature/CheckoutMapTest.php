<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CheckoutMapTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'name' => 'Ali Valiyev',
        'phone' => '+998 90 123 45 67',
        'city' => 'Tashkent',
        'address' => 'Amir Temur',
        'house_number' => '10',
        'apartment' => '12',
        'entrance' => '2',
        'floor' => '3',
        'district' => 'Mirzo Ulugbek',
        'region' => 'Tashkent Region',
        'country' => 'Uzbekistan',
        'country_code' => 'uz',
        'postcode' => '100000',
        'delivery_lat' => '41.2995012',
        'delivery_lng' => '69.2401034',
        'delivery_method' => 'morning',
        'payment_method' => 'cod',
        'note' => 'Call first',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['geocoding.nominatim.rate_per_second' => 0]);
    }

    private function userWithCart(): User
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->for(Product::factory())->create(['price' => '10.00', 'stock' => 10, 'is_active' => true]);
        Cart::create(['user_id' => $user->id])->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        return $user;
    }

    private function nominatimReverse(): array
    {
        return [
            'lat' => '41.2995', 'lon' => '69.2401', 'display_name' => 'Amir Temur 10, Tashkent',
            'address' => ['house_number' => '10', 'road' => 'Amir Temur', 'suburb' => 'Mirzo Ulugbek', 'city' => 'Tashkent', 'state' => 'Tashkent Region', 'postcode' => '100000', 'country' => 'Uzbekistan', 'country_code' => 'uz'],
        ];
    }

    public function test_checkout_page_contains_the_map_and_all_address_fields(): void
    {
        $user = $this->userWithCart();

        $this->actingAs($user)->get('/checkout')->assertOk()
            ->assertSee('id="delivery-map"', false)
            ->assertSee('leaflet', false)
            ->assertSee('js/pages/checkout-map.js', false)
            ->assertSee('OpenStreetMap', false)
            ->assertSee('name="delivery_lat"', false)
            ->assertSee('name="house_number"', false)
            ->assertSee('name="entrance"', false)
            ->assertSee('name="floor"', false)
            ->assertSee('Recipient name')
            ->assertSee('Delivery instructions');
    }

    public function test_order_keeps_coordinates_and_structured_address(): void
    {
        $user = $this->userWithCart();

        $this->actingAs($user)->post('/checkout', $this->form)->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('41.2995012', $order->delivery_lat);
        $this->assertSame('69.2401034', $order->delivery_lng);
        $this->assertSame('UZ', $order->delivery_country_code);
        $this->assertSame('Amir Temur', $order->delivery_street);
        $this->assertSame('10', $order->delivery_house_number);
        $this->assertSame('Amir Temur 10, 12, entrance 2, floor 3, Mirzo Ulugbek, Tashkent', $order->address);
        $this->assertTrue($order->hasLocation());
        $this->assertStringContainsString('openstreetmap.org', $order->map_url);

        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee('View on map');
    }

    public function test_manual_address_without_a_pin_still_works(): void
    {
        $user = $this->userWithCart();

        $this->actingAs($user)->post('/checkout', array_diff_key($this->form, array_flip(['delivery_lat', 'delivery_lng'])))
            ->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertNull($order->delivery_lat);
        $this->assertFalse($order->hasLocation());
        $this->assertSame('Tashkent', $order->delivery_city);
    }

    public function test_coordinates_are_validated_on_the_server(): void
    {
        $user = $this->userWithCart();

        foreach ([
            ['delivery_lat' => '91', 'delivery_lng' => '10'],
            ['delivery_lat' => '10', 'delivery_lng' => '181'],
            ['delivery_lat' => 'abc', 'delivery_lng' => '10'],
            ['delivery_lat' => '10', 'delivery_lng' => null],
            ['delivery_lat' => '0', 'delivery_lng' => '0'],
        ] as $bad) {
            $this->actingAs($user)->post('/checkout', $bad + $this->form)->assertSessionHasErrors();
        }

        $this->assertSame(0, Order::count());
    }

    public function test_bad_country_code_and_postcode_are_rejected(): void
    {
        $user = $this->userWithCart();

        $this->actingAs($user)->post('/checkout', ['country_code' => 'UZB', 'postcode' => '<script>'] + $this->form)
            ->assertSessionHasErrors(['country_code', 'postcode']);
    }

    public function test_pin_can_be_made_mandatory(): void
    {
        config(['geocoding.require_pin' => true]);
        $user = $this->userWithCart();

        $this->actingAs($user)->post('/checkout', array_diff_key($this->form, array_flip(['delivery_lat', 'delivery_lng'])))
            ->assertSessionHasErrors('delivery_lat');
    }

    public function test_last_order_location_is_used_as_the_starting_pin(): void
    {
        $user = $this->userWithCart();
        $this->actingAs($user)->post('/checkout', $this->form);

        $variant = ProductVariant::factory()->for(Product::factory())->create(['price' => '10.00', 'stock' => 10, 'is_active' => true]);
        Cart::where('user_id', $user->id)->first()->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($user)->get('/checkout')->assertOk()
            ->assertSee('data-pin-lat="41.2995012"', false)
            ->assertSee('data-pin-source="saved"', false);
    }

    public function test_reverse_geocoding_endpoint_returns_normalised_fields_and_caches(): void
    {
        Http::fake(['*' => Http::response($this->nominatimReverse())]);
        $user = User::factory()->create();

        $first = $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 41.2995, 'lng' => 69.2401])->assertOk();
        $first->assertJsonPath('address.street', 'Amir Temur')->assertJsonPath('address.house_number', '10')
            ->assertJsonPath('address.country_code', 'UZ')->assertJsonPath('address.district', 'Mirzo Ulugbek');

        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 41.2995, 'lng' => 69.2401])->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('User-Agent') && $request['format'] === 'jsonv2');
    }

    public function test_reverse_geocoding_validates_input_and_requires_login(): void
    {
        Http::fake();

        $this->postJson('/checkout/geocode/reverse', ['lat' => 1, 'lng' => 2])->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 100, 'lng' => 2])->assertUnprocessable();
        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 'x', 'lng' => 2])->assertUnprocessable();

        Http::assertNothingSent();
    }

    public function test_reverse_geocoding_handles_no_result_and_provider_failure(): void
    {
        $user = User::factory()->create();

        // Http::fake qayta chaqirilsa birinchi javob ustun qoladi, shuning uchun javoblar ketma-ket beriladi.
        Http::fake(['*' => Http::sequence()
            ->push(['error' => 'Unable to geocode'])
            ->push('boom', 500)
            ->push($this->nominatimReverse())]);

        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 10, 'lng' => 10])->assertNotFound();
        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 20, 'lng' => 20])->assertStatus(503);
        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 20, 'lng' => 20])->assertOk(); // xato keshlanmagan
    }

    public function test_provider_requests_are_rate_limited(): void
    {
        config(['geocoding.nominatim.rate_per_second' => 1]);
        RateLimiter::clear('geocoder:nominatim');
        Http::fake(['*' => Http::response($this->nominatimReverse())]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 1, 'lng' => 1])->assertOk();
        $this->actingAs($user)->postJson('/checkout/geocode/reverse', ['lat' => 2, 'lng' => 2])->assertStatus(429);

        Http::assertSentCount(1);
    }

    public function test_address_search_works_on_explicit_request(): void
    {
        Http::fake(['*' => Http::response([$this->nominatimReverse()])]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/checkout/geocode/search', ['q' => 'Amir Temur 10'])
            ->assertOk()->assertJsonCount(1, 'results')->assertJsonPath('results.0.city', 'Tashkent');

        $this->actingAs($user)->postJson('/checkout/geocode/search', ['q' => 'ab'])->assertUnprocessable();
    }
}
