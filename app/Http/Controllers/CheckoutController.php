<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders) {}

    public function show(Request $request): View|RedirectResponse
    {
        $cart = $this->cart->summary();

        if ($cart['items'] === []) {
            return redirect()->route('catalog.index')->with('status', 'Your cart is empty. Add something first.');
        }

        $freeFrom = Money::cents(config('shop.free_delivery_from'));
        $methods = [];

        foreach (config('shop.delivery_methods') as $key => $method) {
            $fee = $cart['subtotal_cents'] >= $freeFrom ? 0 : Money::cents($method['fee']);
            $methods[$key] = $method + ['fee_value' => Money::format($fee)];
        }

        return view('pages.checkout', [
            'map' => config('geocoding.map'),
            'pin' => $this->pin($request),
            'cart' => $cart,
            'methods' => $methods,
            'payments' => config('shop.payment_methods'),
            'user' => $request->user(),
        ]);
    }

    /** Forma maydonlarida bo'shliqlar tozalanadi (uzunlik tekshiruvi tozalangan qiymatga qo'llanadi). */
    private const TEXT_FIELDS = ['name', 'city', 'address', 'apartment', 'country', 'region', 'district', 'house_number', 'postcode', 'entrance', 'floor'];

    public function store(Request $request): RedirectResponse
    {
        $request->merge(collect(self::TEXT_FIELDS)
            ->filter(fn (string $key): bool => is_string($request->input($key)))
            ->mapWithKeys(fn (string $key): array => [$key => Str::squish($request->input($key))])
            ->all());

        $pinRule = config('geocoding.require_pin') ? 'required' : 'nullable';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'min:7', 'max:32', 'regex:/^[0-9+()\-\s]+$/'],
            'city' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:150'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'delivery_method' => ['required', Rule::in(array_keys(config('shop.delivery_methods')))],
            'payment_method' => ['required', Rule::in(array_keys(config('shop.payment_methods')))],
            'note' => ['nullable', 'string', 'max:1000'],
            'country' => ['nullable', 'string', 'max:80'],
            'country_code' => ['nullable', 'string', 'regex:/^[A-Za-z]{2}$/'],
            'region' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'house_number' => ['nullable', 'string', 'max:20'],
            'postcode' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'entrance' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:10'],
            'delivery_lat' => [$pinRule, 'numeric', 'between:-90,90', 'required_with:delivery_lng'],
            'delivery_lng' => [$pinRule, 'numeric', 'between:-180,180', 'required_with:delivery_lat', function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                // (0, 0) odatda "joy tanlanmagan" belgisi, haqiqiy yetkazish manzili emas.
                if ((float) $value === 0.0 && (float) $request->input('delivery_lat') === 0.0) {
                    $fail('Please choose a real delivery location on the map.');
                }
            }],
        ], [
            'delivery_lat.required' => 'Please drop a pin on the map to show where we should deliver.',
            'delivery_lng.required' => 'Please drop a pin on the map to show where we should deliver.',
        ]);

        $hasPin = isset($data['delivery_lat'], $data['delivery_lng']);

        $order = $this->orders->place($request->user(), [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $this->formatAddress($data),
            'delivery_method' => $data['delivery_method'],
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
            'delivery' => [
                // Koordinatalar serverda tekshirilgan va 7 xonagacha yaxlitlangan; buyurtma shu foydalanuvchiniki.
                'delivery_lat' => $hasPin ? round((float) $data['delivery_lat'], 7) : null,
                'delivery_lng' => $hasPin ? round((float) $data['delivery_lng'], 7) : null,
                'delivery_country' => $data['country'] ?? null,
                'delivery_country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
                'delivery_region' => $data['region'] ?? null,
                'delivery_city' => $data['city'],
                'delivery_district' => $data['district'] ?? null,
                'delivery_street' => $data['address'],
                'delivery_house_number' => $data['house_number'] ?? null,
                'delivery_postcode' => $data['postcode'] ?? null,
                'delivery_apartment' => $data['apartment'] ?? null,
                'delivery_entrance' => $data['entrance'] ?? null,
                'delivery_floor' => $data['floor'] ?? null,
            ],
        ]);

        return redirect()
            ->route('orders.show', $order)
            ->with('status', "Order {$order->number} placed. Thank you!");
    }

    /**
     * Buyurtmaning to'liq manzil matni. Shahar ALOHIDA oxirida qoladi: hamkor panelidagi "City" ustuni
     * (OrderItemResource) manzilning oxirgi ", " dan keyingi qismini shahar deb oladi.
     *
     * @param  array<string, mixed>  $data
     */
    private function formatAddress(array $data): string
    {
        $head = collect([
            trim($data['address'].' '.($data['house_number'] ?? '')),
            $data['apartment'] ?? null,
            ! empty($data['entrance']) ? 'entrance '.$data['entrance'] : null,
            ! empty($data['floor']) ? 'floor '.$data['floor'] : null,
            $data['district'] ?? null,
        ])->filter()->implode(', ');

        $tail = ', '.$data['city'];

        return Str::limit($head, 255 - mb_strlen($tail), '').$tail;
    }

    /**
     * Xarita boshlang'ich pini: avval xato bilan qaytgan forma (old), keyin foydalanuvchining oxirgi buyurtma joyi.
     *
     * @return array{lat: mixed, lng: mixed, source: ?string}
     */
    private function pin(Request $request): array
    {
        if (is_numeric(old('delivery_lat')) && is_numeric(old('delivery_lng'))) {
            return ['lat' => old('delivery_lat'), 'lng' => old('delivery_lng'), 'source' => 'old'];
        }

        $last = $request->user()?->orders()->whereNotNull('delivery_lat')->whereNotNull('delivery_lng')->latest('id')->first(['delivery_lat', 'delivery_lng']);

        return $last
            ? ['lat' => $last->delivery_lat, 'lng' => $last->delivery_lng, 'source' => 'saved']
            : ['lat' => null, 'lng' => null, 'source' => null];
    }
}
