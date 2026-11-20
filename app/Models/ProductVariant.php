<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'discount_percentage',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_percentage' => 'integer',
            'stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function hasDiscount(): Attribute
    {
        return Attribute::get(fn () => (int) $this->discount_percentage > 0);
    }

    /** Chegirmadan keyingi narx ("4.49" ko'rinishida). */
    protected function finalPrice(): Attribute
    {
        return Attribute::get(function () {
            $price = (float) $this->price;
            $discount = (int) $this->discount_percentage;

            if ($discount > 0) {
                $price = round($price * (100 - $discount) / 100, 2);
            }

            return number_format($price, 2, '.', '');
        });
    }
}
