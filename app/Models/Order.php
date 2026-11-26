<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    /** Tuzilmali yetkazish maydonlari (OrderService::place faqat shularni qabul qiladi). */
    public const DELIVERY_FIELDS = [
        'delivery_lat', 'delivery_lng', 'delivery_country', 'delivery_country_code', 'delivery_region',
        'delivery_city', 'delivery_district', 'delivery_street', 'delivery_house_number', 'delivery_postcode',
        'delivery_apartment', 'delivery_entrance', 'delivery_floor',
    ];

    protected $fillable = [
        'user_id',
        'customer_name',
        'status',
        'subtotal',
        'tax',
        'delivery_fee',
        'total_price',
        'delivery_method',
        'payment_method',
        'phone',
        'address',
        'note',
        ...self::DELIVERY_FIELDS,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_price' => 'decimal:2',
            'delivery_lat' => 'decimal:7',
            'delivery_lng' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** "FM-00042" */
    protected function number(): Attribute
    {
        return Attribute::get(fn () => 'FM-'.str_pad((string) $this->getKey(), 5, '0', STR_PAD_LEFT));
    }

    public function hasLocation(): bool
    {
        return $this->delivery_lat !== null && $this->delivery_lng !== null;
    }

    /** Kuryer/operator uchun OpenStreetMap havolasi. */
    protected function mapUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->hasLocation()
            ? sprintf('https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=17/%1$s/%2$s', $this->delivery_lat, $this->delivery_lng)
            : null);
    }

    public function isCancellableByCustomer(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    /** Buyurtmani bekor qiladi va ombordagi qoldiqni qaytaradi (ikki marta ishlamaydi). */
    public function cancel(): void
    {
        DB::transaction(function (): void {
            $order = static::query()->whereKey($this->getKey())->lockForUpdate()->first();

            if (! $order || in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Completed], true)) {
                return;
            }

            foreach ($order->items()->get() as $item) {
                if ($item->product_variant_id) {
                    ProductVariant::query()
                        ->whereKey($item->product_variant_id)
                        ->increment('stock', (float) $item->quantity);
                }
            }

            $order->update(['status' => OrderStatus::Cancelled]);
        });

        $this->refresh();
    }
}
