set -euo pipefail

fail() {
  echo "Xato: $1" >&2
  exit 1
}

[ -f artisan ] && [ -f routes/web.php ] || fail "loyiha ildizida ishga tushiring"
[ -f routes/tma.php ] && [ -f resources/views/tma/layout.blade.php ] || fail "avval tma-a.sh ni ishga tushiring"

if [ -f app/Http/Controllers/Tma/ShopController.php ]; then
  echo "B qism allaqachon qo'llangan."
  exit 0
fi

mkdir -p app/Http/Controllers/Tma resources/views/tma tests/Feature

cat > app/Http/Controllers/Tma/CheckoutController.php <<'EOF'
<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\CheckoutController as WebCheckoutController;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cartService): View|RedirectResponse
    {
        $cart = $cartService->summary();

        if ($cart['items'] === []) {
            return redirect()->route('tma.home')->with('status', 'Your cart is empty. Add something first.');
        }

        $freeFrom = Money::cents(config('shop.free_delivery_from'));
        $methods = [];

        foreach (config('shop.delivery_methods') as $key => $method) {
            $fee = $cart['subtotal_cents'] >= $freeFrom ? 0 : Money::cents($method['fee']);
            $methods[$key] = $method + ['fee_value' => Money::format($fee)];
        }

        $user = $request->user();

        return view('tma.checkout', [
            'cart' => $cart,
            'methods' => $methods,
            'payments' => config('shop.payment_methods'),
            'user' => $user,
            'phone' => $user->phone ?: $user->orders()->latest('id')->value('phone'),
        ]);
    }

    /** Tekshiruv va buyurtma yaratish saytdagi bilan bitta joyda: faqat yakuniy sahifa boshqacha. */
    public function store(Request $request, WebCheckoutController $checkout): RedirectResponse
    {
        $checkout->store($request);

        $order = $request->user()->orders()->latest('id')->firstOrFail();

        return redirect()
            ->route('tma.orders.show', $order)
            ->with('status', "Order {$order->number} placed. Thank you!");
    }
}
EOF

cat > app/Http/Controllers/Tma/OrderController.php <<'EOF'
<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('tma.orders', [
            'orders' => $request->user()->orders()->withCount('items')->latest('id')->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('tma.order', ['order' => $order->load('items')]);
    }
}
EOF

cat > app/Http/Controllers/Tma/ShopController.php <<'EOF'
<?php

namespace App\Http\Controllers\Tma;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    private const PER_PAGE = 12;

    public function catalog(Request $request): View
    {
        $rawSearch = $request->query('q');
        $search = is_string($rawSearch) ? Str::of($rawSearch)->squish()->limit(100, '')->toString() : '';
        $discounted = $request->boolean('discounted');

        $categories = Category::query()->roots()->orderBy('name')->get(['id', 'parent_id', 'name', 'slug']);
        $category = $categories->firstWhere('slug', $request->query('category'));

        $query = Product::query()->active()->forCard()->has('activeVariants')->latest('id');

        if ($category) {
            $query->whereIn('category_id', $category->branchIds());
        }

        if ($search !== '') {
            $query->where(function (Builder $where) use ($search) {
                $where->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($discounted) {
            $query->whereHas('activeVariants', fn (Builder $variants) => $variants->where('discount_percentage', '>', 0));
        }

        return view('tma.catalog', [
            'products' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'categories' => $categories,
            'category' => $category,
            'search' => $search,
            'discounted' => $discounted,
        ]);
    }

    public function product(Product $product): View
    {
        abort_unless($product->isVisible(), 404);

        $product->load(['brand', 'unit', 'images', 'activeVariants']);

        abort_if($product->activeVariants->isEmpty(), 404);

        return view('tma.product', ['product' => $product]);
    }

    public function cart(CartService $cartService): View
    {
        $cart = $cartService->summary();

        return view('tma.cart', [
            'cart' => $cart,
            'slugs' => Product::query()->whereIn('id', array_column($cart['items'], 'product_id'))->pluck('slug', 'id'),
        ]);
    }
}
EOF

cat > routes/tma.php <<'EOF'
<?php

use App\Http\Controllers\Tma\AuthController;
use App\Http\Controllers\Tma\CheckoutController;
use App\Http\Controllers\Tma\OrderController;
use App\Http\Controllers\Tma\ShopController;
use App\Http\Middleware\EnsureTelegramUser;
use Illuminate\Support\Facades\Route;

Route::prefix('tma')->name('tma.')->group(function () {
    Route::view('login', 'tma.login')->name('login');
    Route::post('auth', [AuthController::class, 'store'])->middleware('throttle:20,1,tma-auth')->name('auth');

    Route::middleware(EnsureTelegramUser::class)->group(function () {
        Route::get('/', [ShopController::class, 'catalog'])->name('home');
        Route::get('products/{product:slug}', [ShopController::class, 'product'])->name('products.show');
        Route::get('cart', [ShopController::class, 'cart'])->name('cart');

        Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout');
        Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
});
EOF

cat > resources/views/tma/nav.blade.php <<'EOF'
@inject('tmaCart', 'App\Services\CartService')
@php($tmaCount = $tmaCart->summary()['count'])
<nav class="tabbar" aria-label="Shop sections">
    <a href="{{ route('tma.home') }}" class="{{ request()->routeIs('tma.home', 'tma.products.show') ? 'on' : '' }}"><b aria-hidden="true">🛍</b>Shop</a>
    <a href="{{ route('tma.cart') }}" class="{{ request()->routeIs('tma.cart', 'tma.checkout') ? 'on' : '' }}">
        <b aria-hidden="true">🛒</b>Cart
        <i class="count" id="tma-cart-count" @if (! $tmaCount) hidden @endif>{{ $tmaCount }}</i>
    </a>
    <a href="{{ route('tma.orders.index') }}" class="{{ request()->routeIs('tma.orders.*') ? 'on' : '' }}"><b aria-hidden="true">📦</b>Orders</a>
</nav>
EOF

cat > resources/views/tma/catalog.blade.php <<'EOF'
@extends('tma.layout')

@section('content')
<p class="muted small">Hi, {{ auth()->user()->name }}</p>

<form method="GET" action="{{ route('tma.home') }}" class="search" role="search">
    <input type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search products" aria-label="Search products">
    @if ($category)
        <input type="hidden" name="category" value="{{ $category->slug }}">
    @endif
    @if ($discounted)
        <input type="hidden" name="discounted" value="1">
    @endif
    <button type="submit" class="btn">Search</button>
</form>

<div class="chips">
    <a href="{{ route('tma.home') }}" class="{{ ! $category && ! $discounted ? 'on' : '' }}">All</a>
    <a href="{{ route('tma.home', ['discounted' => 1]) }}" class="{{ $discounted ? 'on' : '' }}">Deals %</a>
    @foreach ($categories as $item)
        <a href="{{ route('tma.home', ['category' => $item->slug]) }}" class="{{ $category?->id === $item->id ? 'on' : '' }}">{{ $item->name }}</a>
    @endforeach
</div>

@if ($products->isEmpty())
    <div class="center">
        <p>Nothing found.</p>
        <a href="{{ route('tma.home') }}" class="btn">Show all products</a>
    </div>
@else
    <div class="grid">
        @foreach ($products as $product)
            @php($variant = $product->cardVariant())
            @continue(! $variant)

            <article class="card">
                <a href="{{ route('tma.products.show', $product->slug) }}" class="thumb">
                    @if ($product->mainImage)
                        <img src="{{ $product->mainImage->url }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                        <span aria-hidden="true">🛒</span>
                    @endif
                </a>
                <h3><a href="{{ route('tma.products.show', $product->slug) }}">{{ $product->name }}</a></h3>
                <span class="muted small">{{ $variant->name }}</span>
                <div>
                    <span class="price">${{ $variant->final_price }}</span>
                    @if ($variant->has_discount)
                        <span class="old">${{ $variant->price }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('cart.items.store') }}" data-ajax>
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                    <input type="hidden" name="quantity" value="1">
                    @if ((float) $variant->stock >= 1)
                        <button type="submit" class="btn btn-small">Add</button>
                    @else
                        <button type="button" class="btn btn-small" disabled>Sold out</button>
                    @endif
                </form>
            </article>
        @endforeach
    </div>

    @if ($products->hasPages())
        <div class="pager">
            @if ($products->previousPageUrl())
                <a href="{{ $products->previousPageUrl() }}" class="btn btn-ghost">Previous</a>
            @else
                <span></span>
            @endif
            @if ($products->nextPageUrl())
                <a href="{{ $products->nextPageUrl() }}" class="btn btn-ghost">Next</a>
            @endif
        </div>
    @endif
@endif
@endsection
EOF

cat > resources/views/tma/product.blade.php <<'EOF'
@extends('tma.layout')

@section('title', $product->name)
@section('back', route('tma.home'))

@section('content')
@php
    $variants = $product->activeVariants->sortBy(fn ($v) => (float) $v->final_price)->values();
    $picked = $variants->first(fn ($v) => (float) $v->stock >= 1);
@endphp

<div class="gallery">
    @forelse ($product->images->sortByDesc('is_main') as $image)
        <div class="thumb"><img src="{{ $image->url }}" alt="{{ $product->name }}"></div>
    @empty
        <div class="thumb"><span aria-hidden="true">🛒</span></div>
    @endforelse
</div>

<p class="muted small">{{ $product->brand->name }}</p>
<h1>{{ $product->name }}</h1>
@if ($product->description)
    <p class="muted">{{ $product->description }}</p>
@endif

<form method="POST" action="{{ route('cart.items.store') }}" data-ajax>
    @csrf
    <h2>Choose a size</h2>
    @foreach ($variants as $variant)
        <label class="option">
            <input type="radio" name="variant_id" value="{{ $variant->id }}" required @checked($picked?->id === $variant->id) @disabled((float) $variant->stock < 1)>
            <span class="grow">
                {{ $variant->name }}
                @if ((float) $variant->stock < 1)
                    <span class="muted small">(sold out)</span>
                @endif
            </span>
            <span>
                <span class="price">${{ $variant->final_price }}</span>
                @if ($variant->has_discount)
                    <span class="old">${{ $variant->price }}</span>
                @endif
            </span>
        </label>
    @endforeach

    <label class="field">
        <span>Quantity</span>
        <input type="number" name="quantity" value="1" min="1" max="99" inputmode="numeric">
    </label>

    <button type="submit" class="btn btn-block" @disabled(! $picked)>{{ $picked ? 'Add to cart' : 'Sold out' }}</button>
</form>
@endsection
EOF

cat > resources/views/tma/cart.blade.php <<'EOF'
@extends('tma.layout')

@section('title', 'Cart')
@section('back', route('tma.home'))

@section('content')
<h1>Your cart</h1>

@if ($cart['items'] === [])
    <div class="center">
        <p>Your cart is empty.</p>
        <a href="{{ route('tma.home') }}" class="btn">Start shopping</a>
    </div>
@else
    @foreach ($cart['items'] as $item)
        <div class="line">
            <div class="thumb">
                @if ($item['img'])
                    <img src="{{ $item['img'] }}" alt="{{ $item['name'] }}">
                @else
                    <span aria-hidden="true">🛒</span>
                @endif
            </div>
            <div class="grow">
                <a href="{{ route('tma.products.show', $slugs[$item['product_id']]) }}"><b>{{ $item['name'] }}</b></a>
                <div class="muted small">{{ $item['variant'] }} &middot; ${{ $item['price'] }}</div>
                <div class="stepper">
                    <form method="POST" action="{{ $item['qty'] > 1 ? route('cart.items.update', $item['id']) : route('cart.items.destroy', $item['id']) }}" data-once>
                        @csrf
                        @method($item['qty'] > 1 ? 'PATCH' : 'DELETE')
                        <input type="hidden" name="quantity" value="{{ $item['qty'] - 1 }}">
                        <button type="submit" class="btn btn-ghost btn-small" aria-label="Decrease {{ $item['name'] }}">&minus;</button>
                    </form>
                    <b>{{ $item['qty'] }}</b>
                    <form method="POST" action="{{ route('cart.items.update', $item['id']) }}" data-once>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="quantity" value="{{ $item['qty'] + 1 }}">
                        <button type="submit" class="btn btn-ghost btn-small" aria-label="Increase {{ $item['name'] }}" @disabled($item['qty'] >= $item['max'])>+</button>
                    </form>
                    <form method="POST" action="{{ route('cart.items.destroy', $item['id']) }}" data-once>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-small">Remove</button>
                    </form>
                </div>
            </div>
            <b>${{ $item['line_total'] }}</b>
        </div>
    @endforeach

    <div class="row"><span class="muted">Subtotal</span><span>${{ $cart['subtotal'] }}</span></div>
    @if ($cart['tax_rate'])
        <div class="row"><span class="muted">Tax</span><span>${{ $cart['tax'] }}</span></div>
    @endif
    <div class="row"><span class="muted">Delivery</span><span>{{ $cart['qualifies'] ? 'FREE' : 'at checkout' }}</span></div>
    @unless ($cart['qualifies'])
        <p class="muted small">Add ${{ $cart['remaining'] }} more for free delivery.</p>
    @endunless
    <div class="row total"><span>Total</span><span>${{ $cart['total'] }}</span></div>

    <a href="{{ route('tma.checkout') }}" class="btn btn-block">Checkout</a>
@endif
@endsection
EOF

cat > resources/views/tma/checkout.blade.php <<'EOF'
@extends('tma.layout')

@section('title', 'Checkout')
@section('back', route('tma.cart'))

@section('content')
@php
    $delivery = array_key_exists(old('delivery_method', ''), $methods) ? old('delivery_method') : array_key_first($methods);
    $payment = array_key_exists(old('payment_method', ''), $payments) ? old('payment_method') : array_key_first($payments);
@endphp

<h1>Checkout</h1>

<form method="POST" action="{{ route('tma.checkout.store') }}" data-once>
    @csrf

    <label class="field"><span>Full name</span>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="120" autocomplete="name" required>
    </label>
    <label class="field"><span>Phone</span>
        <input type="tel" name="phone" value="{{ old('phone', $phone) }}" minlength="7" maxlength="32" autocomplete="tel" placeholder="+998 90 123 45 67" required>
    </label>
    <label class="field"><span>City</span>
        <input type="text" name="city" value="{{ old('city') }}" maxlength="50" autocomplete="address-level2" required>
    </label>
    <label class="field"><span>Street address</span>
        <input type="text" name="address" value="{{ old('address', $user->address) }}" maxlength="150" autocomplete="address-line1" required>
    </label>
    <label class="field"><span>Apartment / entrance (optional)</span>
        <input type="text" name="apartment" value="{{ old('apartment') }}" maxlength="50" autocomplete="address-line2">
    </label>

    <input type="hidden" id="delivery_lat" name="delivery_lat" value="{{ old('delivery_lat') }}">
    <input type="hidden" id="delivery_lng" name="delivery_lng" value="{{ old('delivery_lng') }}">
    <button type="button" id="tma-locate" class="btn btn-ghost btn-small">📍 Add my location</button>
    <span id="tma-locate-info" class="muted small" role="status">{{ old('delivery_lat') ? 'Location added.' : '' }}</span>

    <h2>Delivery</h2>
    @foreach ($methods as $key => $method)
        <label class="option">
            <input type="radio" name="delivery_method" value="{{ $key }}" data-fee="{{ $method['fee_value'] }}" @checked($delivery === $key)>
            <span class="grow">{{ $method['name'] }}<br><span class="muted small">{{ $method['text'] }}</span></span>
            <b>{{ (float) $method['fee_value'] === 0.0 ? 'FREE' : '$'.$method['fee_value'] }}</b>
        </label>
    @endforeach

    <h2>Payment</h2>
    @foreach ($payments as $key => $item)
        <label class="option">
            <input type="radio" name="payment_method" value="{{ $key }}" @checked($payment === $key)>
            <span class="grow">{{ $item['name'] }}<br><span class="muted small">{{ $item['text'] }}</span></span>
        </label>
    @endforeach

    <label class="field"><span>Note for the courier (optional)</span>
        <textarea name="note" rows="2" maxlength="1000">{{ old('note') }}</textarea>
    </label>

    <div class="row total">
        <span>Total</span>
        <span id="tma-total" data-base="{{ $cart['total'] }}">${{ number_format((float) $cart['total'] + (float) $methods[$delivery]['fee_value'], 2) }}</span>
    </div>

    <button type="submit" class="btn btn-block">Place order</button>
    <p class="muted small">You pay on delivery. No card details are collected here.</p>
</form>
@endsection
EOF

cat > resources/views/tma/orders.blade.php <<'EOF'
@extends('tma.layout')

@section('title', 'Orders')
@section('back', route('tma.home'))

@section('content')
<h1>My orders</h1>

@forelse ($orders as $order)
    <a href="{{ route('tma.orders.show', $order) }}" class="order">
        <div class="row">
            <b>{{ $order->number }}</b>
            <span class="status status-{{ $order->status->value }}">{{ $order->status->label() }}</span>
        </div>
        <div class="row muted small">
            <span>{{ $order->created_at->format('d M Y, H:i') }} &middot; {{ $order->items_count }} {{ $order->items_count === 1 ? 'item' : 'items' }}</span>
            <span>${{ $order->total_price }}</span>
        </div>
    </a>
@empty
    <div class="center">
        <p>No orders yet.</p>
        <a href="{{ route('tma.home') }}" class="btn">Start shopping</a>
    </div>
@endforelse

@if ($orders->hasPages())
    <div class="pager">
        @if ($orders->previousPageUrl())
            <a href="{{ $orders->previousPageUrl() }}" class="btn btn-ghost">Newer</a>
        @else
            <span></span>
        @endif
        @if ($orders->nextPageUrl())
            <a href="{{ $orders->nextPageUrl() }}" class="btn btn-ghost">Older</a>
        @endif
    </div>
@endif
@endsection
EOF

cat > resources/views/tma/order.blade.php <<'EOF'
@extends('tma.layout')

@section('title', $order->number)
@section('back', route('tma.orders.index'))

@section('content')
<h1>Order {{ $order->number }}</h1>
<p>
    <span class="status status-{{ $order->status->value }}">{{ $order->status->label() }}</span>
    <span class="muted small">{{ $order->created_at->format('d M Y, H:i') }}</span>
</p>

@foreach ($order->items as $item)
    <div class="row">
        <span>{{ $item->product_name }}<br><span class="muted small">{{ $item->variant_name }} &middot; ${{ $item->price }} x {{ (int) $item->quantity }}</span></span>
        <b>${{ number_format((float) $item->price * (float) $item->quantity, 2) }}</b>
    </div>
@endforeach

<div class="row sep"><span class="muted">Subtotal</span><span>${{ $order->subtotal }}</span></div>
@if ((float) $order->tax > 0)
    <div class="row"><span class="muted">Tax</span><span>${{ $order->tax }}</span></div>
@endif
<div class="row"><span class="muted">Delivery</span><span>{{ (float) $order->delivery_fee === 0.0 ? 'FREE' : '$'.$order->delivery_fee }}</span></div>
<div class="row total"><span>Total</span><span>${{ $order->total_price }}</span></div>

<h2>Delivery to</h2>
<p>{{ $order->customer_name }}, {{ $order->phone }}<br><span class="muted">{{ $order->address }}</span></p>
<p class="muted small">
    {{ config('shop.delivery_methods')[$order->delivery_method]['name'] ?? $order->delivery_method }} &middot;
    {{ config('shop.payment_methods')[$order->payment_method]['name'] ?? $order->payment_method }}
</p>

@if ($order->isCancellableByCustomer())
    <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Cancel this order?">
        @csrf
        <button type="submit" class="btn btn-danger btn-block">Cancel order</button>
    </form>
@endif
@endsection
EOF

rm -f resources/views/tma/home.blade.php

cat > tests/Feature/TelegramShopTest.php <<'EOF'
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
EOF

echo "B qism qo'llandi."
